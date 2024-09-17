<?php

namespace App\Http\Controllers;

use App\Http\Requests\Template\TemplateRequest;
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
            $result = Template::where('name','like',"%$search%")->paginate(15);
        }else{
            $result = Template::all()->paginate(15);
        }

        return response()->json([
            'results'=>$result,
        ]);
    }

    public function updateTemplate(TemplateRequest $request,$id): JsonResponse
    {
        $template= Template::findOrFail($id);

        $template->name =$request->input('name');
        $template->description=$request->input('description');
        $template->save();

        return response()->json([
            'message'=>'Template updated successfully'
        ]);
    }

    public function deleteTemplate($id): JsonResponse
    {
        Template::where('id', $id)->delete();

        return response()->json([],204);
    }

}
