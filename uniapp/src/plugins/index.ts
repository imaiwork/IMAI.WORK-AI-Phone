import { isFunction } from '@vue/shared'
import { App } from 'vue'
const modules = import.meta.glob('./modules/**/*.ts', { eager: true })

export default {
    install: (app: App) => {
        for (const module of Object.values(modules)) {
            const fun = (module as any).default
            isFunction(fun) && fun(app)
        }
    }
}
