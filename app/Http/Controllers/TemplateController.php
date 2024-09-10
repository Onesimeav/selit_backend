<?php

namespace App\Http\Controllers;

use App\Http\Requests\TemplateRequest;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function createTemplate(TemplateRequest $request): JsonResponse
    {
        $template=Template::create([
            'name'=>$request->input('name'),
            'description'=>$request->input('description'),
        ]);

        return response()->json([
            'message'=>'Template created successfully',
            'template_id'=>$template->id,
        ],201);
    }

    public function searchTemplate(Request $request): JsonResponse
    {
        $search = $request->input('search');
        if ($search!=null){
            $result = Template::where('name','like',"%$search%")->get();
        }else{
            $result = Template::all();
        }

        return response()->json([
            'results'=>$result,
        ]);
    }

    public function updateTemplate(TemplateRequest $request): JsonResponse
    {
        $template_id=$request->input('template_id');

        $template= Template::findOrFail($template_id);

        $template->name =$request->input('name');
        $template->description=$request->input('description');
        $template->save();

        return response()->json([
            'message'=>'Template updated successfully'
        ]);
    }

    public function deleteTemplate(Request $request): JsonResponse
    {
        $template_id=$request->input('template_id');

        Template::where('id', $template_id)->delete();

        return response()->json([],204);
    }

}
