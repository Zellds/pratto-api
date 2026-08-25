<?php

namespace App\Http\Controllers;

use App\Application\Follow\UseCases\GetFeed;
use App\Http\Resources\RecipeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    public function index(Request $request, GetFeed $getFeed): AnonymousResourceCollection
    {
        $data = $request->validate(['page' => ['nullable', 'integer', 'min:1']]);

        return RecipeResource::collection(
            $getFeed($request->user()->id, $data['page'] ?? 1, 20),
        );
    }
}
