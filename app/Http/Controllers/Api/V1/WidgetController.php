<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var KnowledgeBase $knowledgeBase */
        $knowledgeBase = $request->attributes->get('knowledgeBase');

        return response()->json([
            'id' => $knowledgeBase->widget_token,
            'name' => $knowledgeBase->name,
            'welcome_message' => $knowledgeBase->welcome_message,
            'primary_color' => $knowledgeBase->primary_color,
        ]);
    }
}
