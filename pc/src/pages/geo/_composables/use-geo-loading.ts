import { ref } from 'vue'

/** 筛选/切换时的局部 loading，并用序号挡住过期请求回写 */
export function useGeoLoading() {
    const contentLoading = ref(false)
    let seq = 0

    const beginLoad = () => {
        const id = ++seq
        contentLoading.value = true
        return id
    }

    const isLatest = (id: number) => id === seq

    const endLoad = (id: number) => {
        if (id === seq) contentLoading.value = false
    }

    return { contentLoading, beginLoad, isLatest, endLoad }
}
