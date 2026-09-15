<template>
    <view>
        <u-popup v-model="showPopup" mode="bottom" border-radius="14" :mask-close-able="false">
            <view class="p-[40rpx]">
                <view class="flex items-center">
                    <image class="w-[100rpx] h-[100rpx] rounded" mode="heightFix" :src="logo"></image>
                    <text class="text-3xl ml-5 font-medium">{{ title }}</text>
                </view>
                <view class="mt-5 text-muted">
                    {{
                        requireInvite
                            ? "当前站点开启了邀请码注册，请先填写邀请码完成注册"
                            : "建议使用您的微信头像和昵称，以便获得更好的体验"
                    }}
                </view>
                <view class="mt-[30rpx]">
                    <form @submit="handleSubmit">
                        <u-form-item v-if="requireInvite" required label="邀请码" :labelWidth="120">
                            <input
                                v-model="inviteCode"
                                class="flex-1 h-[60rpx]"
                                name="invite_code"
                                type="text"
                                maxlength="32"
                                placeholder="请输入邀请码" />
                        </u-form-item>
                        <template v-else>
                            <u-form-item required label="头像" :labelWidth="120">
                                <view class="flex-1">
                                    <avatar-upload v-model="avatar" @upload="uploadImg"></avatar-upload>
                                </view>
                            </u-form-item>
                            <u-form-item required label="昵称" :labelWidth="120">
                                <input
                                    v-model="nickname"
                                    class="flex-1 h-[60rpx]"
                                    name="nickname"
                                    type="nickname"
                                    placeholder="请输入昵称" />
                            </u-form-item>
                            <u-form-item label="性别" :labelWidth="120">
                                <view class="flex-1">
                                    <u-radio-group v-model="sex">
                                        <u-radio
                                            v-for="item in SEX_OPTIONS"
                                            :key="item.value"
                                            :name="item.value"
                                            label-size="26"
                                            size="28">
                                            {{ item.label }}
                                        </u-radio>
                                    </u-radio-group>
                                </view>
                            </u-form-item>
                            <u-form-item v-if="showStoreName" label="所在门店" :labelWidth="120">
                                <input
                                    v-model="storeName"
                                    class="flex-1 h-[60rpx]"
                                    type="text"
                                    maxlength="100"
                                    placeholder="请输入所在门店（选填）" />
                            </u-form-item>
                            <u-form-item v-if="showCertificate" label="资质证书" :labelWidth="120">
                                <view class="flex-1">
                                    <view
                                        class="w-[140rpx] h-[140rpx] rounded overflow-hidden"
                                        @click="handleUploadCertificate">
                                        <image
                                            v-if="certificateImage"
                                            class="w-full h-full"
                                            mode="aspectFill"
                                            :src="certificateImage" />
                                        <view
                                            v-else
                                            class="w-full h-full box-border border border-dotted border-light rounded flex flex-col items-center justify-center text-muted text-xs">
                                            <u-icon name="plus" :size="36" />
                                            上传证书
                                        </view>
                                    </view>
                                </view>
                            </u-form-item>
                        </template>
                        <view class="mt-[80rpx]">
                            <button
                                class="bg-primary rounded-full text-white text-lg h-[80rpx] leading-[80rpx]"
                                hover-class="none"
                                form-type="submit">
                                {{ requireInvite ? "下一步" : "确定" }}
                            </button>
                        </view>

                        <view class="flex justify-center mt-[60rpx]">
                            <view class="text-muted" @click="showPopup = false"> 取消 </view>
                        </view>
                    </form>
                </view>
            </view>
        </u-popup>
    </view>
</template>

<script lang="ts" setup>
import { uploadImage } from "@/api/app";
import { chooseFile } from "@/components/file-upload/choose-file";
import { useAppStore } from "@/stores/app";
import { computed, ref } from "vue";

// 性别取值与后端保持一致:0=未知 1=男 2=女,新用户默认未知
const SEX_UNKNOWN = 0;
const SEX_OPTIONS = [
    { label: "女", value: 2 },
    { label: "男", value: 1 },
    { label: "未知", value: SEX_UNKNOWN },
];

const props = defineProps({
    show: {
        type: Boolean,
    },
    logo: {
        type: String,
    },
    title: {
        type: String,
    },
    userInfo: {
        type: Object,
    },
    /** 后台注册方式=邀请码且一键登录未带码:此处补填后再注册 */
    requireInvite: {
        type: Boolean,
        default: false,
    },
    /** 邀请码预填值(代理二维码 sn / 旧版 code) */
    inviteCode: {
        type: String,
        default: "",
    },
});
const emit = defineEmits<{
    (event: "update:show", show: boolean): void;
    (event: "update", value: any): void;
}>();

const showPopup = computed({
    get() {
        return props.show;
    },
    set(val) {
        emit("update:show", val);
    },
});

const avatar = ref();
const nickname = ref();
const inviteCode = ref(props.inviteCode || "");
const sex = ref(SEX_UNKNOWN);
const storeName = ref("");
const certificateImage = ref("");

// 门店/资质证书由后台「用户设置-注册资料填写项」控制,老后端没下发配置时默认展示
const appStore = useAppStore();
const profileFields = computed<Record<string, any>>(() => appStore.getUserProfileFields);
const showStoreName = computed(() => Number(profileFields.value.store_name_open ?? 1) === 1);
const showCertificate = computed(() => Number(profileFields.value.certificate_image_open ?? 1) === 1);

const uploadImg = async (file: string) => {
    uni.showLoading({
        title: "正在上传中...",
    });
    try {
        const res: any = await uploadImage(file, {}, props.userInfo?.token);
        avatar.value = res.uri;
        uni.hideLoading();
    } catch (error) {
        console.log(error);
        uni.hideLoading();
        uni.$u.toast("上传失败，请重试");
    }
};

// 资质证书走本组件自己的上传:注册阶段用户还没进 store,token 只在 loginData 里,
// 通用 useUpload 无法带这个 token,所以与同组件的头像上传保持一致
const isCertificateUploading = ref(false);
const handleUploadCertificate = async () => {
    if (isCertificateUploading.value) return;
    let filePath = "";
    try {
        const res = await chooseFile({ type: "image", count: 1 });
        filePath = res.tempFiles?.[0]?.tempFilePath || "";
    } catch (error) {
        return;
    }
    if (!filePath) return;

    isCertificateUploading.value = true;
    uni.showLoading({
        title: "正在上传中...",
    });
    try {
        const res: any = await uploadImage(filePath, {}, props.userInfo?.token);
        certificateImage.value = res.uri;
    } catch (error) {
        console.log(error);
        uni.$u.toast("上传失败，请重试");
    } finally {
        isCertificateUploading.value = false;
        uni.hideLoading();
    }
};

const handleSubmit = (e: any) => {
    // 第一步:补邀请码完成注册(此时还没有 token,头像传不上去)
    if (props.requireInvite) {
        const code = String(inviteCode.value || "").trim();
        if (!code) return uni.$u.toast("请输入邀请码");
        return emit("update", { invite_code: code });
    }
    const { nickname } = e.detail.value;
    if (!avatar.value) return uni.$u.toast("请添加头像");
    if (!nickname) return uni.$u.toast("请输入昵称");
    // 性别、所在门店、资质证书都是选填,未开启的资料项不提交
    const profile: Record<string, any> = {
        avatar: avatar.value,
        nickname,
        sex: sex.value,
    };
    if (showStoreName.value) {
        profile.store_name = String(storeName.value || "").trim();
    }
    if (showCertificate.value) {
        profile.certificate_image = certificateImage.value || "";
    }
    emit("update", profile);
};
watch(
    () => props.inviteCode,
    (val) => {
        if (val && !inviteCode.value) inviteCode.value = val;
    },
    { immediate: true },
);
watch(
    () => props.userInfo,
    (val) => {
        if (val) {
            avatar.value = val.avatar;
            nickname.value = val.nickname;
            // 个人中心/设置页复用本弹窗时回显已填资料;注册场景这些字段不存在,保持默认
            sex.value = Number(val.sex_value ?? SEX_UNKNOWN);
            storeName.value = val.store_name || "";
            certificateImage.value = val.certificate_image || "";
        }
    },
);
</script>

<style lang="scss" scoped></style>
