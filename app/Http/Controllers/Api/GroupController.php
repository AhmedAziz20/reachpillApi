<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Category;
use App\Group;

class GroupController extends Controller
{
    public function addGroup(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'category_id' => 'required'
        ]);

        $category = Category::find($validatedData['category_id']) ;
        if($category){
            $group = $category->groups()->create($validatedData);
            return response()->json($group,201);
        }
        return response()->json(['message' => 'category not found'],404);

    }

    public function getGroup($id){
        $groups = Category::find($id)->groups;
        return response()->json($groups,200);
    }

    public function getGroups(){
        $groups = Group::with('category')->paginate(20);
        return response()->json($groups,200);
    }

    public function getGroupsCosmetic(){
        $groups = Group::with('category')->get();
        return response()->json($groups,200);
    }

    public function deleteGroup(Request $request){
        $validatedData = $request->validate([
            'id' => 'required'
        ]);

        $group = Group::find($validatedData['id']);
        $group->delete();

        return response()->json(['status'=>true],200);
    }

    public function getGroupById($id){
        $group = Group::find($id);
        $cosmetics = $group->cosmetics()->paginate(20);

        return response()->json(['group'=>$group,'cosmetics'=>$cosmetics],200);
    }
}
