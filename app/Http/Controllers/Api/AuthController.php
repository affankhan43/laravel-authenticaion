<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\User;
use App\User_verifications;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Controllers\Controller;
use Validator, DB, Hash, Mail;
use Illuminate\Support\Facades\Password;
class AuthController extends Controller
{
	private $user;
	private $jwtauth;
	private $user_verifications;

	public function __construct(User $user, JWTAuth $jwtauth, User_verifications $user_verifications){
		$this->user = $user;
		$this->jwtauth = $jwtauth;
		$this->user_verifications = $user_verifications;
	}

    public function register(Request $request){
		$credentials = $request->only('name', 'email', 'password');
		$rules = [
			'name' => 'required|max:255',
			'email' => 'required|email|max:255|unique:users',
			'password'=>'required|min:8'
		];
		$validator = Validator::make($credentials, $rules);
		if($validator->fails()) {
			return response()->json(['success'=> false, 'error'=> $validator->messages()]);
		}
		$user = $this->user->create(['name'=>$request->name,'email'=>$request->email,'password'=>Hash::make($request->password)]);
		$verification_code = str_random(30); //Generate verification code
        $this->user_verifications->create(['user_id'=>$user->id,'token'=>$verification_code]);
        return response()->json(['success'=> true, 'message'=> 'Thanks for signing up!']);
    }

	public function verifyUser($verification_code){
		$check = $this->user_verifications->where('token',$verification_code)->first();
		if(!is_null($check)){
			$user = $this->user->find($check->user_id);
			if($user->is_verified == 1){
				return response()->json([
					'success'=> true,
					'message'=> 'Account already verified..'
				]);
			}
			$user->update(['is_verified' => 1]);
			$this->user_verifications->where('token',$verification_code)->delete();
			return response()->json(['success'=> true,'message'=> 'You have successfully verified your email address.']);
		}
		return response()->json(['success'=> false, 'error'=> "Verification code is invalid."]);
	}

	public function login(Request $request){
		$credentials = $request->only('email', 'password');
		$rules = [
			'email' => 'required|email',
			'password' => 'required',
		];
		if($validator->fails()) {
			return response()->json(['success'=> false, 'error'=> $validator->messages()], 401);
		}
		$credentials['is_verified'] = 1;
		try {
            // attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['success' => false, 'error' => 'We cant find an account with this credentials. Please make sure you entered the right information and you have verified your email address.'], 404);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['success' => false, 'error' => 'Failed to login, please try again.'], 500);
        }
        // all good so return the token
        return response()->json(['success' => true, 'data'=> [ 'token' => $token ]], 200);
    }

	public function logout(Request $request) {
		$this->validate($request, ['token' => 'required']);
		try {
			$this->jwtauth->invalidate($request->input('token'));
			return response()->json(['success' => true, 'message'=> "You have successfully logged out."]);
		}
		catch (JWTException $e) {
			// something went wrong whilst attempting to encode the token
			return response()->json(['success' => false, 'error' => 'Failed to logout, please try again.'], 500);
		}
	}


}