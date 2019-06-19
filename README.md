# ThrottleRequest
接口限频

####env文件里添加以下配置
- cache:
   - CACHE_DRIVER=redis
   - CACHE_PREFIX=your_prefix
 
- redis:
   - REDIS_HOST=172.28.249.11
   - REDIS_PORT=6379
   - REDIS_PASSWORD=

####bootstrap/app.php增加中间件别名
   - $app->routeMiddleware([
        'throttle' => App\Http\Middleware\ThrottleRequests::class,
    ]);
####路由进行限频设置
   - lumen版本5.4及5.4之前:
   $app->group([
         'middleware' => [
             'throttle:20,1'//1分钟限制20次
         ]
     ], function ($app) {
     });
   - lumen版本5.4之后:
    $router->group([
             'middleware' => [
                 'throttle:20,1'//1分钟限制20次
             ]
         ], function ($app) {
         });
