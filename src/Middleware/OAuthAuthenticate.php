<?php

namespace Goodwong\LaravelDingtalkOAuth\Middleware;

use Log;
use Closure;
use Illuminate\Http\Request;
use Goodwong\LaravelDingtalk\Handlers\DingtalkHandler;
use Goodwong\LaravelDingtalk\Events\DingtalkUserAuthorized;
use Goodwong\LaravelDingtalk\Services\DingtalkService;
use Goodwong\LaravelDingtalk\Repositories\DingtalkUserRepository;

class OAuthAuthenticate
{
    /**
     * construct
     * 
     * @param  DingtalkHandler  $dingtalkHandler
     * @param  DingtalkUserRepository  $dingtalkUserRepository
     * @param  DingtalkService  $service
     * @return void
     */
    public function __construct(
        DingtalkHandler $dingtalkHandler,
        DingtalkUserRepository $dingtalkUserRepository,
        DingtalkService $dingtalkService
    ) {
        $this->dingtalkHandler = $dingtalkHandler;
        $this->dingtalkUserRepository = $dingtalkUserRepository;
        $this->dingtalkService = $dingtalkService;
    }

    /**
     * Handle an incoming request.
     * 
     * @param Request  $request
     * @param \Closure  $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 已登录用户
        if ($request->user()) {
            return $next($request);
        }

        // 非钉钉，跳过
        if (!$this->isDingtalkBrowser($request)) {
            return $next($request);
        }

        // 有缓存
        $exist = session('dingtalk.oauth_user');
        if ($exist) {
            $dingtalkUser = $this->dingtalkUserRepository->find($exist['id']);
            event(new DingtalkUserAuthorized($dingtalkUser));
            return $next($request);
        }

        // 没有code，过
        if (!$request->has('code')) {
            return $next($request);
        }

        // 解析 code
        $code = $request->input('code');
        $profile = $this->dingtalkService->getProfileFromCode($code);
        Log::info('[dingtalk_login] original info: ', (array)$profile);
        $dingtalkUser = $this->dingtalkUserRepository
        ->scopeQuery(function ($query) use ($profile) {
            return $query->where('userid', $profile->userid);
        })->first();
        if (!$dingtalkUser) {
            $dingtalkUser = $this->dingtalkHandler->create((array)$profile);
        }
        event(new DingtalkUserAuthorized($dingtalkUser));
        session(['dingtalk.oauth_user' => $dingtalkUser]);

        return redirect()->to($this->getTargetUrl($request));
    }

    /**
     * Build the target business url.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function getTargetUrl($request)
    {
        $queries = array_except($request->query(), ['code', 'state']);
        return $request->url().(empty($queries) ? '' : '?'.http_build_query($queries));
    }

    /**
     * Detect current user agent type.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    protected function isDingtalkBrowser($request)
    {
        return stripos($request->header('user_agent'), 'Dingtalk') !== false;
    }
}