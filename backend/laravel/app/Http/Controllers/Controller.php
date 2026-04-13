<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Shipping Label Manager API",
 *     version="1.0.0",
 *     description="REST API for generating, storing, and managing USPS shipping labels via EasyPost. All label-related endpoints require a Bearer token obtained from /api/login or /api/register."
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="Local development server (http://localhost:8080/api)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="token",
 *     description="Sanctum personal access token. Pass as: Authorization: Bearer {token}"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id",                 type="integer", example=1),
 *     @OA\Property(property="name",               type="string",  example="Jane Doe"),
 *     @OA\Property(property="email",              type="string",  format="email", example="jane@example.com"),
 *     @OA\Property(property="email_verified_at",  type="string",  format="date-time", nullable=true),
 *     @OA\Property(property="created_at",         type="string",  format="date-time"),
 *     @OA\Property(property="updated_at",         type="string",  format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ShippingLabel",
 *     type="object",
 *     @OA\Property(property="id",                    type="integer", example=1),
 *     @OA\Property(property="user_id",               type="integer", example=1),
 *     @OA\Property(property="easypost_shipment_id",  type="string",  example="shp_abc123"),
 *     @OA\Property(property="tracking_code",         type="string",  example="9400111899223397711668", nullable=true),
 *     @OA\Property(property="carrier",               type="string",  example="USPS"),
 *     @OA\Property(property="service",               type="string",  example="Priority"),
 *     @OA\Property(property="label_url",             type="string",  format="uri", example="https://easypost-files.s3.amazonaws.com/files/postage_label/abc.pdf"),
 *     @OA\Property(property="label_file_type",       type="string",  enum={"PDF","PNG","ZPL"}, example="PDF"),
 *     @OA\Property(property="rate",                  type="number",  format="float", example=7.50),
 *     @OA\Property(property="from_name",             type="string",  example="Jane Doe"),
 *     @OA\Property(property="from_company",          type="string",  nullable=true),
 *     @OA\Property(property="from_street1",          type="string",  example="388 Townsend St"),
 *     @OA\Property(property="from_street2",          type="string",  nullable=true),
 *     @OA\Property(property="from_city",             type="string",  example="San Francisco"),
 *     @OA\Property(property="from_state",            type="string",  example="CA"),
 *     @OA\Property(property="from_zip",              type="string",  example="94107"),
 *     @OA\Property(property="to_name",               type="string",  example="John Smith"),
 *     @OA\Property(property="to_company",            type="string",  nullable=true),
 *     @OA\Property(property="to_street1",            type="string",  example="1600 Pennsylvania Ave NW"),
 *     @OA\Property(property="to_street2",            type="string",  nullable=true),
 *     @OA\Property(property="to_city",               type="string",  example="Washington"),
 *     @OA\Property(property="to_state",              type="string",  example="DC"),
 *     @OA\Property(property="to_zip",                type="string",  example="20500"),
 *     @OA\Property(property="weight",                type="number",  format="float", example=16.0, description="Ounces"),
 *     @OA\Property(property="length",                type="number",  format="float", example=12.0, description="Inches"),
 *     @OA\Property(property="width",                 type="number",  format="float", example=8.0,  description="Inches"),
 *     @OA\Property(property="height",                type="number",  format="float", example=4.0,  description="Inches"),
 *     @OA\Property(property="status",                type="string",  example="purchased"),
 *     @OA\Property(property="created_at",            type="string",  format="date-time"),
 *     @OA\Property(property="updated_at",            type="string",  format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="AuthResponse",
 *     type="object",
 *     @OA\Property(property="user",  ref="#/components/schemas/User"),
 *     @OA\Property(property="token", type="string", example="1|abc123xyz")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="The given data was invalid.")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="The email field is required."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\AdditionalProperties(
 *             type="array",
 *             @OA\Items(type="string")
 *         ),
 *         example={"email": {"The email field is required."}}
 *     )
 * )
 */
abstract class Controller
{
    //
}
