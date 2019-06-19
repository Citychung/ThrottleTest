<?php
namespace CThrottle\Throttle;
use App\Classes\Header;
use App\Enums\StatusCode;
use Closure;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

abstract class ThrottleRequests
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new request throttler.
     *
     * @param \Illuminate\Cache\RateLimiter $limiter
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param int $maxAttempts
     * @param float|int $decayMinutes
     * @return mixed
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            $this->buildResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes);

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    /* 参考例子
    protected function resolveRequestSignature($request)
    {
        //这里是生成请求的key，可以根据实际需求进行修改，Header一开始就把http header里的参数记录下来了，方便全局使用
        return sha1(
            $request->method() .
            '|' . $request->path() .
            '|' . Header::$ip .
            '|' . Header::$uid .
            '|' . Header::$staff_id .
            '|' . Header::$sig
        );
    }
    */
    abstract protected function resolveRequestSignature($request);

        //这里是生成请求的key，可以根据实际需求进行修改，Header一开始就把http header里的参数记录下来了，方便全局使用



    /**
     * Create a 'too many attempts' response.
     *
     * @param string $key
     * @param int $maxAttempts
     * @throws \Exception
     */
    /*参考例子
     protected function buildResponse($key, $maxAttempts)
    {
        $retryAfter = $this->limiter->availableIn($key);

        $msg = _('接口调用次数过多，请稍后重试：') . $retryAfter . 's';

        throw new \Exception($msg, StatusCode::TOO_MANY_ATTEMPT);
    }
    */
     protected function buildResponse($key, $maxAttempts) {
         $retryAfter = $this->limiter->availableIn($key);

         $msg = _('接口调用次数过多，请稍后重试：') . $retryAfter . 's';

         throw new \Exception($msg, StatusCode::TOO_MANY_ATTEMPT);
     }




    /**
     * Add the limit header information to the given response.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param int $maxAttempts
     * @param int $remainingAttempts
     * @param int|null $retryAfter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];

        if (!is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
            $headers['X-RateLimit-Reset'] = Carbon::now()->getTimestamp() + $retryAfter;
        }

        $response->headers->add($headers);

        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param string $key
     * @param int $maxAttempts
     * @param int|null $retryAfter
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        if (is_null($retryAfter)) {
            return $this->limiter->retriesLeft($key, $maxAttempts);
        }

        return 0;
    }
}