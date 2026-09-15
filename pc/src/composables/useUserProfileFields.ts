import { useAppStore } from "@/stores/app";

/**
 * 注册资料填写项开关（后台「用户设置-注册资料填写项」）
 * 老后端没下发该配置时默认展示，避免升级顺序不同步导致字段消失
 */
export function useUserProfileFields() {
    const appStore = useAppStore();
    const profileFields = computed<Record<string, any>>(() => appStore.getUserProfileFields);
    const showStoreName = computed(() => Number(profileFields.value.store_name_open ?? 1) === 1);
    const showCertificate = computed(() => Number(profileFields.value.certificate_image_open ?? 1) === 1);

    return { showStoreName, showCertificate };
}
