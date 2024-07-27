<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MenuUtility
{
    /**
     * Get URL from JSON based on the provided path.
     *
     * @param string $menuPath
     * @return array|null
     */
    public static function getUrlFromJson($menuPath)
    {
        try {
            $jsonString = Storage::disk('public')->get('menus455.json');
            $jsonData = json_decode($jsonString, true);
            return $jsonData[$menuPath] ?? null;
        } catch (\Exception $e) {
            Log::error("An error occurred: " . $e->getMessage());
            return null;
        }
    }
}
