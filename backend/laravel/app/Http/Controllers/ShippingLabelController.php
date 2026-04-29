<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateShippingLabelRequest;
use App\Models\ShippingLabel;
use App\Services\EasyPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ShippingLabelController extends Controller
{
    // US state/territory codes accepted for address validation
    public const US_STATES = [
        'AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA',
        'HI','ID','IL','IN','IA','KS','KY','LA','ME','MD',
        'MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ',
        'NM','NY','NC','ND','OH','OK','OR','PA','RI','SC',
        'SD','TN','TX','UT','VT','VA','WA','WV','WI','WY',
        'DC',
    ];

    public function __construct(private readonly EasyPostService $easyPost) {}

    /**
     * @OA\Get(
     *     path="/labels",
     *     tags={"Shipping Labels"},
     *     summary="List labels",
     *     description="Returns all shipping labels that belong to the authenticated user, ordered newest first.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of labels",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ShippingLabel"))
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $labels = $request->user()
            ->shippingLabels()
            ->latest()
            ->get();

        return response()->json($labels);
    }

    /**
     * @OA\Post(
     *     path="/labels",
     *     tags={"Shipping Labels"},
     *     summary="Create a shipping label",
     *     description="Validates the from/to addresses and package dimensions, calls EasyPost to purchase the cheapest available USPS rate, and persists the resulting label. Only US addresses are accepted.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"from_name","from_street1","from_city","from_state","from_zip","to_name","to_street1","to_city","to_state","to_zip","weight","length","width","height"},
     *             @OA\Property(property="from_name",    type="string", example="Jane Doe"),
     *             @OA\Property(property="from_company", type="string", nullable=true, example="Acme Inc."),
     *             @OA\Property(property="from_street1", type="string", example="388 Townsend St"),
     *             @OA\Property(property="from_street2", type="string", nullable=true, example="Suite 1"),
     *             @OA\Property(property="from_city",    type="string", example="San Francisco"),
     *             @OA\Property(property="from_state",   type="string", description="2-letter US state code", example="CA"),
     *             @OA\Property(property="from_zip",     type="string", description="5-digit or ZIP+4 format", example="94107"),
     *             @OA\Property(property="to_name",      type="string", example="John Smith"),
     *             @OA\Property(property="to_company",   type="string", nullable=true),
     *             @OA\Property(property="to_street1",   type="string", example="1600 Pennsylvania Ave NW"),
     *             @OA\Property(property="to_street2",   type="string", nullable=true),
     *             @OA\Property(property="to_city",      type="string", example="Washington"),
     *             @OA\Property(property="to_state",     type="string", description="2-letter US state code", example="DC"),
     *             @OA\Property(property="to_zip",       type="string", example="20500"),
     *             @OA\Property(property="weight", type="number", format="float", description="Package weight in ounces", example=16),
     *             @OA\Property(property="length", type="number", format="float", description="Package length in inches", example=12),
     *             @OA\Property(property="width",  type="number", format="float", description="Package width in inches",  example=8),
     *             @OA\Property(property="height", type="number", format="float", description="Package height in inches", example=4)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Label created and purchased",
     *         @OA\JsonContent(ref="#/components/schemas/ShippingLabel")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or EasyPost API failure",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(CreateShippingLabelRequest $request): JsonResponse
    {
        try {
            $easyPostData = $this->easyPost->createLabel($request->validated());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $label = $request->user()->shippingLabels()->create([
            ...$request->validated(),
            ...$easyPostData,
        ]);

        return response()->json($label, 201);
    }

    /**
     * @OA\Get(
     *     path="/labels/{id}",
     *     tags={"Shipping Labels"},
     *     summary="Get a label",
     *     description="Returns a single shipping label. Returns 403 if the label belongs to a different user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Label ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Label details",
     *         @OA\JsonContent(ref="#/components/schemas/ShippingLabel")
     *     ),
     *     @OA\Response(response=403, description="Forbidden — label belongs to another user", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Label not found",                           @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated",                           @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(Request $request, ShippingLabel $label): JsonResponse
    {
        $this->authorizeLabel($request, $label);

        return response()->json($label);
    }

    /**
     * @OA\Get(
     *     path="/labels/{id}/download",
     *     tags={"Shipping Labels"},
     *     summary="Download / print label",
     *     description="Redirects (302) to the EasyPost-hosted label PDF/PNG, suitable for printing. Returns 403 if the label belongs to a different user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Label ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to label PDF URL",
     *         @OA\Header(header="Location", description="EasyPost label URL", @OA\Schema(type="string", format="uri"))
     *     ),
     *     @OA\Response(response=403, description="Forbidden — label belongs to another user", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Label not found",                           @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated",                           @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function download(Request $request, ShippingLabel $label): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeLabel($request, $label);

        return redirect()->away($label->label_url);
    }

    private function authorizeLabel(Request $request, ShippingLabel $label): void
    {
        if ($label->user_id !== $request->user()->id) {
            abort(403, 'This label does not belong to you.');
        }
    }
}
