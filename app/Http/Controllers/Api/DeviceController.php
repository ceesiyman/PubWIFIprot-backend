<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Device\StoreDeviceRequest;
use App\Http\Requests\Device\UpdateDeviceRequest;

/**
 * @OA\Tag(
 *     name="Devices",
 *     description="API Endpoints for managing user devices"
 * )
 */
class DeviceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/devices",
     *     summary="Get all devices for the authenticated user",
     *     tags={"Devices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of devices",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="device_type", type="string"),
     *                 @OA\Property(property="os_version", type="string"),
     *                 @OA\Property(property="app_version", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $devices = auth()->user()->devices;
        return response()->json($devices);
    }

    /**
     * @OA\Post(
     *     path="/api/devices",
     *     summary="Register a new device",
     *     tags={"Devices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_type", "os_version", "app_version"},
     *             @OA\Property(property="device_type", type="string", example="iPhone"),
     *             @OA\Property(property="os_version", type="string", example="iOS 15.0"),
     *             @OA\Property(property="app_version", type="string", example="1.0.0")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Device registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Device registered successfully"),
     *             @OA\Property(property="device", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $device = auth()->user()->devices()->create($request->validated());
        return response()->json([
            'message' => 'Device registered successfully',
            'device' => $device
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/devices/{device}",
     *     summary="Get device details",
     *     tags={"Devices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="device",
     *         in="path",
     *         required=true,
     *         description="Device ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="device_type", type="string"),
     *             @OA\Property(property="os_version", type="string"),
     *             @OA\Property(property="app_version", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Device not found"
     *     )
     * )
     */
    public function show(Device $device): JsonResponse
    {
        $this->authorize('view', $device);
        return response()->json($device);
    }

    /**
     * @OA\Put(
     *     path="/api/devices/{device}",
     *     summary="Update device information",
     *     tags={"Devices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="device",
     *         in="path",
     *         required=true,
     *         description="Device ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="device_type", type="string", example="iPhone"),
     *             @OA\Property(property="os_version", type="string", example="iOS 15.1"),
     *             @OA\Property(property="app_version", type="string", example="1.0.1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Device updated successfully"),
     *             @OA\Property(property="device", type="object")
     *         )
     *     )
     * )
     */
    public function update(UpdateDeviceRequest $request, Device $device): JsonResponse
    {
        $this->authorize('update', $device);
        $device->update($request->validated());
        return response()->json([
            'message' => 'Device updated successfully',
            'device' => $device
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/devices/{device}",
     *     summary="Delete a device",
     *     tags={"Devices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="device",
     *         in="path",
     *         required=true,
     *         description="Device ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Device deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(Device $device): JsonResponse
    {
        $this->authorize('delete', $device);
        $device->delete();
        return response()->json(['message' => 'Device deleted successfully']);
    }
} 