<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * [Description UserController]
 */
class UserController extends Controller
{

	private const LOGIN_FAIL_INCORRECT_FORMAT = 0;
	private const LOGIN_FAIL_UNKNOWN_USER = 1;
	private const LOGIN_FAIL_INCORRECT_PASSWORD = 2;

	/**
	 * API User login
	 * 
	 * @param Request $request
	 * 
	 * @return Response A HTTP response with the API token if the login is successful
	 */
	public function login(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'email' => 'required|email',
			'password' => 'required'
		]);

		if ($validator->fails()) {
			return response(['message' => 'Login failed. ('.self::LOGIN_FAIL_INCORRECT_FORMAT.')'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$user = User::where('email', $request->email)->first();
		if ($user) {
			if (Hash::check($request->password, $user->password)) {
				$token = $user->createToken('Laravel Password Grant Client')->accessToken;
				return response(['token' => $token], Response::HTTP_OK);
			} else {
				return response(['message' => 'Login failed. ('.self::LOGIN_FAIL_INCORRECT_PASSWORD.')'], Response::HTTP_UNPROCESSABLE_ENTITY);
			}
		} else {
			return response(['message' => 'Login failed. ('.self::LOGIN_FAIL_UNKNOWN_USER.')'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}
	}


	/**
	 * Register an API user
	 * 
	 * @param Request $request
	 * 
	 * @return Response An HTTP response with details of failure or success
	 */
	public function register(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'required|string|max:255',
			'email' => 'required|email|max:255|unique:users',
			'password' => 'required|string|min:8|confirmed'
		]);

		if ($validator->fails()) {
			return response(['errors' => $validator->errors()->all()], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$request['password'] = Hash::make($request['password']);
		$request['remember_token'] = Str::random(10);

		$user = User::create($request->toArray());
		if (!$user->exists) {
			return response(['message' => "Couldn't create user"], Response::HTTP_UNPROCESSABLE_ENTITY);
		}
		return response()->json(['message' => 'User created successfully'], Response::HTTP_CREATED);
	}

	/**
	 * Log the API user out and revoke the user's issued token
	 * 
	 * @param Request $request
	 * 
	 * @return Response An HTTP response
	 */
	public function logout(Request $request)
	{
		$token = $request->user()->token();
		$token->revoke();
		return response(['message' => 'Successfully logged out.'], Response::HTTP_OK);
	}
}
