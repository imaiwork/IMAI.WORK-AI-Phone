import { defineStore } from 'pinia'
import router from '@/router'
interface routeObject {
    path: string
    title: string
}
interface NavigationBarTitleState {
    pathToTitleMap: Map<string, string | undefined>
}
export const useNavigationBarTitleStore = defineStore({
    id: 'navigationBarTitleStore',
    state: (): NavigationBarTitleState => ({
        pathToTitleMap: new Map()
    }),
    getters: {
        getTitle(): string | undefined {
            try {
                const realRoute = router.resolve(router.currentRoute.value.path)
                return this.pathToTitleMap.get(realRoute?.path!)
            } catch (error) {}
        }
    },
    actions: {
        add({ path, title }: routeObject) {
            this.pathToTitleMap.set(path, title)
        },
        setTitle() {
            const title = this.getTitle
            if (title) {
                uni.setNavigationBarTitle({
                    title
                })
            }
        }
    }
})
