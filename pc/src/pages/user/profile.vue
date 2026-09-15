<template>
    <div class="p-6 h-full bg-slate-50">
        <div class="bg-white h-full flex rounded-[20px] flex-col p-8 border border-br overflow-y-auto">
            <div class="flex items-center gap-2 pb-6 border-b border-br-light">
                <div class="w-1.5 h-5 rounded-full bg-primary"></div>
                <span class="text-xl font-[900] text-[#0F172A]">个人资料</span>
            </div>

            <ElForm class="mt-8 max-w-[560px]" label-width="100px" label-position="left">
                <ElFormItem label="头像">
                    <div class="flex items-center gap-4">
                        <ElImage
                            v-if="formData.avatar"
                            class="w-[72px] h-[72px] rounded-full border border-solid border-br-light"
                            fit="cover"
                            :src="formData.avatar"
                            :preview-src-list="[formData.avatar]"
                            preview-teleported />
                        <upload
                            :show-file-list="false"
                            :limit="1"
                            :max-size="10"
                            accept=".jpg,.jpeg,.png,.webp"
                            @success="handleAvatarSuccess">
                            <ElButton>更换头像</ElButton>
                        </upload>
                    </div>
                </ElFormItem>
                <ElFormItem label="昵称">
                    <ElInput v-model="formData.nickname" maxlength="32" placeholder="请输入昵称" />
                </ElFormItem>
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
                    <div class="flex items-center gap-4">
                        <ElImage
                            v-if="formData.certificate_image"
                            class="w-[104px] h-[104px] rounded-lg border border-solid border-br-light"
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

                <ElFormItem label="用户编号">
                    <span class="text-tx-regular">{{ userInfo.sn || "--" }}</span>
                </ElFormItem>
                <ElFormItem label="账号">
                    <span class="text-tx-regular">{{ userInfo.account || "--" }}</span>
                </ElFormItem>
                <ElFormItem label="手机号">
                    <span class="text-tx-regular">{{ userInfo.mobile || "未绑定" }}</span>
                </ElFormItem>
                <ElFormItem label="注册时间">
                    <span class="text-tx-regular">{{ userInfo.create_time || "--" }}</span>
                </ElFormItem>

                <ElFormItem>
                    <ElButton type="primary" :loading="isLock" @click="submitLock">保存</ElButton>
                    <ElButton @click="resetForm">重置</ElButton>
                </ElFormItem>
            </ElForm>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { useUserStore } from "@/stores/user";
import { userEdit } from "@/api/user";
import { USER_SEX_OPTIONS, UserSexEnum } from "@/enums/appEnums";
import { useUserProfileFields } from "@/composables/useUserProfileFields";

const userStore = useUserStore();
const { userInfo } = toRefs(userStore);
const { showStoreName, showCertificate } = useUserProfileFields();

const formData = reactive({
    avatar: "",
    nickname: "",
    sex: UserSexEnum.UNKNOWN as number,
    store_name: "",
    certificate_image: "",
});

// 用户中心接口的 sex 是「男/女/未知」文案，回显要用后端另给的原值 sex_value
const resetForm = () => {
    const info = userInfo.value || {};
    formData.avatar = info.avatar || "";
    formData.nickname = info.nickname || "";
    formData.sex = Number(info.sex_value ?? UserSexEnum.UNKNOWN);
    formData.store_name = info.store_name || "";
    formData.certificate_image = info.certificate_image || "";
};

const handleAvatarSuccess = (res: any) => {
    formData.avatar = res?.data?.uri || "";
};

const handleCertificateSuccess = (res: any) => {
    formData.certificate_image = res?.data?.uri || "";
};

// 后端按「字段-值」逐个更新，这里只提交与当前资料不一致的项
const buildEditPayloads = () => {
    const info = userInfo.value || {};
    const payloads: Array<{ field: string; value: string | number }> = [];

    if (formData.avatar && formData.avatar !== info.avatar) {
        payloads.push({ field: "avatar", value: formData.avatar });
    }
    const nickname = formData.nickname.trim();
    if (nickname && nickname !== info.nickname) {
        payloads.push({ field: "nickname", value: nickname });
    }
    if (formData.sex !== Number(info.sex_value ?? UserSexEnum.UNKNOWN)) {
        payloads.push({ field: "sex", value: formData.sex });
    }
    if (showStoreName.value) {
        const storeName = formData.store_name.trim();
        if (storeName && storeName !== info.store_name) {
            payloads.push({ field: "store_name", value: storeName });
        }
    }
    if (showCertificate.value && formData.certificate_image && formData.certificate_image !== info.certificate_image) {
        payloads.push({ field: "certificate_image", value: formData.certificate_image });
    }

    return payloads;
};

const { lockFn: submitLock, isLock } = useLockFn(async () => {
    if (!formData.nickname.trim()) {
        feedback.msgError("请输入昵称");
        return;
    }
    const payloads = buildEditPayloads();
    if (!payloads.length) {
        feedback.msgSuccess("资料未变化");
        return;
    }
    try {
        for (const payload of payloads) {
            await userEdit(payload);
        }
        await userStore.getUser();
        resetForm();
        feedback.msgSuccess("保存成功");
    } catch (error) {
        feedback.msgError(error || "保存失败");
    }
});

// 只在进入页面和切换账号时回填:顶部算力轮询会整体替换 userInfo,
// 深度监听会把用户正在填的内容冲掉
resetForm();
watch(() => userInfo.value?.id, resetForm);

definePageMeta({ layout: "base" });
</script>
