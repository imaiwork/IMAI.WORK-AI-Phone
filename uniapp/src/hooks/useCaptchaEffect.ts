import { ref, onMounted } from 'vue'
import { captcha } from '@/api/account'

const useCaptchaEffect = () => {
    const captchaKey = ref<string>('')
    const captchaImage = ref<string>('')

    const getCaptchaFn = async () => {
        try {
            const data = await captcha()
            captchaKey.value = data.key
            captchaImage.value = data.image
        } catch (error) {
            console.log('获取图形码失败=>', error)
        }
    }

    onMounted(getCaptchaFn)

    return {
        captchaKey,
        captchaImage,
        getCaptchaFn
    }
}

export default useCaptchaEffect
