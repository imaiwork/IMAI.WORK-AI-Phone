import useClipboard from 'vue-clipboard3'
import { ElMessage } from 'element-plus'
export function useCopy() {
    const copy = async (text: string) => {
        const { toClipboard } = useClipboard()
        try {
            await toClipboard(text)
            ElMessage.success({ message: '复制成功' })
        } catch (error) {
            ElMessage.error({ message: '复制失败' })
        }
    }
    return {
        copy
    }
}
