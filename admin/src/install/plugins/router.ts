import router from '@/router'
import type { App } from 'vue'
import { registerRouteGuard } from '@/router/guard'
export default (app: App<Element>) => {
    registerRouteGuard(router)
    app.use(router)
}
