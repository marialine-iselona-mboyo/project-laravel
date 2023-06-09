<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['index', 'show', 'search']]);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index($name)
    {
        $user = User::where('name', '=', $name)->firstOrFail();
        return view('users.profile', compact('user'));
    }

    public function show($id){
        $user = User::findOrFail($id);
        return view('users.show', compact('user'));
      }

    public function store(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image',
        ]);

        $avatarName = time().'.'.$request->avatar->getClientOriginalExtension();
        $request->avatar->move(public_path('avatars'), $avatarName);

        $user = Auth()->user;
        $user->avatar = $avatarName;
        $user->save();

        return back()->with('success', 'Avatar updated successfully.');
    }

    public function edit()
    {
        $user = Auth::user();
        return view('users/edit', compact('user'));

    }


    public function update($id, Request $request){
        $user = User::findOrFail($id);

        if($user->id != Auth::user()->id){
          abort(403);
        }

        $validated = $request->validate([
            'username'      => 'required|min:3',
            'email'         => 'required|min:10',
            'date_of_birth' => 'required|date',
            'about_me'      => 'required|min:20',
            'avatar'        => 'image|max:2048',
        ]);


        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->date_of_birth = $validated['date_of_birth'];
        $user->about_me = $validated['about_me'];
        //$user->save();

        if($request->hasFile('avatar')){
            $filename = $request->file('avatar')->getClientOriginalName();
            $request->file('avatar')->storeAs('avatars',$filename,'public');
            $user->avatar = $filename;
        }


        $user->save();

        return redirect()->route('users/profile', ['name' => $user->name])->with('status', 'Profile Succesfully Edited');

    }

    public function search(Request $request)
    {
        $searchQuery = $request->input('search');

        $users = User::where('name', 'like', '%'.$searchQuery.'%')
                    ->orWhere('username', 'like', '%'.$searchQuery.'%')
                    ->get();

        $posts= Post::where('title', 'like', '%'.$searchQuery.'%')
                    ->get();

        return view('partials/search', compact('users', 'posts'));
    }

}
