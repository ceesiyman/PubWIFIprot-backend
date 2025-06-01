<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="PubWIFIprot API",
 *     version="1.0.0",
 *     description="API documentation for the PubWIFIprot backend."
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your JWT token in the format: Bearer <token>"
 * )
 * 
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Local Development Server"
 * )
 * 
 * @OA\Server(
 *     url="https://api.pubwifiprot.com",
 *     description="Production Server"
 * )
 * 
 * @OA\ExternalDocumentation(
 *     description="Find out more about PubWIFIprot",
 *     url="https://pubwifiprot.com/docs"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 * 
 * @OA\Tag(
 *     name="Networks",
 *     description="API Endpoints for managing and checking WiFi networks"
 * )
 * 
 * @OA\Components(
 *     @OA\Response(
 *         response="UnauthorizedError",
 *         description="Invalid or missing token",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     ),
 *     @OA\Response(
 *         response="ValidationError",
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="field",
 *                     type="array",
 *                     @OA\Items(type="string", example="The field is required.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
abstract class Controller
{
    //
}
