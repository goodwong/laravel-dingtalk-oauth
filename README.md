# Laravel 5 Dingtalk OAuth

钉钉网页登录，并且将钉钉资料存储到数据库

## OAuth 中间件
有两种方式设置中间件：

- 设置 middleware
    在app/Http/Kernel.php里添加：
    ```php
    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [

        // ...

        'dingtalk_oauth' => \Goodwong\DingtalkOAuth\Middleware\OAuthAuthenticate::class,
    ];
    ```

- 直接在web.php的路由规则里添加：
    ```php
    // user auth
    Route::group([
        'middleware' => [
            \Goodwong\DingtalkOAuth\Middleware\OAuthAuthenticate::class,
        ],
    ], function () {
        // ...
    });

    ```


## 配置
在.env文件中，配置以下信息：

- 钉钉平台
    ```ini
    DINGTALK_CORP_ID=
    DINGTALK_CORP_SECRET=
    DINGTALK_AGENT_ID
    ```
    > 这些信息可以在开放平台里注册获取




