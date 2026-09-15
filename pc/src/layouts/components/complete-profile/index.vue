<template>
    <div class="complete-profile-dialog">
        <ElDialog
            v-model="showPopup"
            width="520"
            top="12vh"
            append-to-body
            :close-on-click-modal="false"
            :show-close="false">
            <div class="px-8 py-7">
                <div class="text-[20px] font-medium">完善资料</div>
                <div class="mt-2 text-tx-secondary">以下信息均为选填，可直接跳过，之后可在个人资料中修改</div>

                <ElForm class="mt-6" label-width="90px" label-position="left">
                    <ElFormItem label="性别">
                        <ElRadioGroup v-model="formData.sex">
                            <ElRadio v-for="item in USER_SEX_OPTIONS" :key="item.value" :value="item.value">
                                {{ item.label }}
                            </ElRadio>
                        </ElRadioGroup>
                    </ElFormItem>
                    <ElFormItem v-if="showStoreName" label="所在门店">
                        <ElInput v-model="formData.store_name" maxlength="100" placeholder="请输入所在门店" />
                    </ElFormItem>
                    <ElFormItem v-if="showCertificate" label="资质证书">
                        <div class="flex items-center gap-3">
                            <ElImage
                                v-if="formData.certificate_image"
                                class="w-[88px] h-[88px] rounded border border-solid border-br-light"
                                fit="cover"
                                :src="formData.certificate_image"
                                :preview-src-list="[formData.certificate_image]"
                                preview-teleported />
                            <upload
                                :show-file-list="false"
                                :limit="1"
                                :max-size="10"
                                accept=".jpg,.jpeg,.png,.webp"
                                @success="handleCertificateSuccess">
                                <ElButton>{{ formData.certificate_image ? "更换图片" : "上传图片" }}</ElButton>
                            </upload>
                        </div>
                    </ElFormItem>
                </ElForm>

                <div class="mt-8 flex justify-end gap-3">
                    <ElButton @click="handleSkip">跳过</ElButton>
                    <ElButton type="primary" :loading="isLock" @click="submitLock">保存</ElButton>
                </div>
            </div>
        </ElDialog>
    </div>
</template>

<script lang="ts" setup>
import { useUserStore } from "@/stores/user";
import { userEdit } from "@/api/user";
import { USER_SEX_OPTIONS, UserSexEnum } from "@/enums/appEnums";
import { useUserProfileFields } from "@/composables/useUserProfileFields";

const userStore = useUserStore();
const { showStoreName, showCertificate } = useUserProfileFields();

const showPopup = computed({
    get() {
        return userStore.showCompleteProfile;
    },
    set(value: boolean) {
        userStore.showCompleteProfile = value;
    },
});

const formData = reactive({
    sex: UserSexEnum.UNKNOWN as number,
    store_name: "",
    certificate_image: "",
});

const handleCertificateSuccess = (res: any) => {
    formData.certificate_image = res?.data?.uri || "";
};

// 注册后页面还是注册前的游客视图(壳没重渲染),关闭弹窗统一刷新,与注册流程原有行为一致
const finish = () => {
    showPopup.value = false;
    window.location.reload();
};

const handleSkip = () => {
    finish();
};

// 只提交用户真正填了的项:性别默认「未知」与新账号库里的初始值一致,不必白跑一次接口
const buildEditPayloads = () => {
    const payloads: Array<{ field: string; value: string | number }> = [];
    if (formData.sex !== UserSexEnum.UNKNOWN) {
        payloads.push({ field: "sex", value: formData.sex });
    }
    if (showStoreName.value) {
        const storeName = formData.store_name.trim();
        if (storeName) {
            payloads.push({ field: "store_name", value: storeName });
        }
    }
    if (showCertificate.value && formData.certificate_image) {
        payloads.push({ field: "certificate_image", value: formData.certificate_image });
    }
    return payloads;
};

const { lockFn: submitLock, isLock } = useLockFn(async () => {
    const payloads = buildEditPayloads();
    if (!payloads.length) {
        finish();
        return;
    }
    try {
        for (const payload of payloads) {
            await userEdit(payload);
        }
        finish();
    } catch (error) {
        feedback.msgError(error || "保存失败");
    }
});
</script>

<style lang="scss" scoped>
.complete-profile-dialog {
    :deep() {
        .el-dialog {
            padding: 0;
            overflow: hidden;
            border-radius: var(--el-border-radius-2xl);
        }
        .el-dialog__header {
            display: none;
            padding: 0;
        }
    }
}
</style>
