<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AllblogpostResource;
use App\Models\Allblogpost;
use Illuminate\Http\Request;

class AllblogpostController extends Controller
{
    public function index()
    {
        $posts = Allblogpost::all();
        return AllblogpostResource::collection($posts);
    }
}