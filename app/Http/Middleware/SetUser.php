namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SetUser
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user(); // Assuming you are using Laravel's Auth facade

        if ($user) {
            $request->attributes->set('user', $user);
            Log::info('User set in request: ' . $user->id);
        } else {
            Log::warning('No authenticated user found');
        }

        return $next($request);
    }
}
