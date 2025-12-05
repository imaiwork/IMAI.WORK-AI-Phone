# HTTP 请求工具

## 基本用法

```typescript
import { createRequest } from "@/utils/http";

// 创建请求实例
const request = createRequest();

// 发起GET请求
request.get("/api/users");

// 发起POST请求
request.post("/api/users", { body: { name: "test" } });
```

## 取消请求功能

### 1. 取消单个请求

```typescript
import { createRequest } from "@/utils/http";

// 创建请求实例
const request = createRequest();

// 发起请求
const promise = request.get("/api/users");

// 取消当前请求
request.cancelRequest("用户取消了请求");
```

### 2. 使用取消令牌管理器

```typescript
import { cancelRequest, cancelRequestsByUrl, cancelAllRequests } from "@/utils/http";

// 取消特定请求（需要知道请求的唯一标识）
const requestKey = "GET:/api/users";
cancelRequest(requestKey);

// 取消包含特定URL的所有请求
cancelRequestsByUrl("/api/users");

// 取消所有请求
cancelAllRequests("用户退出登录");
```

### 3. 在路由切换时取消请求

```typescript
import { cancelAllRequests } from "@/utils/http";
import { useRouter } from "vue-router";

const router = useRouter();

// 在路由切换前取消所有未完成的请求
router.beforeEach((to, from, next) => {
    cancelAllRequests("路由切换取消请求");
    next();
});
```

### 4. 取消重复请求

系统已自动处理重复请求的情况。当发起一个与之前完全相同的请求（相同 URL、方法、参数）时，之前的请求会被自动取消。

## 注意事项

1. 请求被取消后，Promise 会被 reject，错误类型为 `AbortError`
2. 可以通过捕获错误来处理请求被取消的情况：

```typescript
request
    .get("/api/users")
    .then((data) => {
        // 处理成功响应
    })
    .catch((err) => {
        if (err.name === "AbortError") {
            console.log("请求被取消");
        } else {
            console.error("请求失败", err);
        }
    });
```
