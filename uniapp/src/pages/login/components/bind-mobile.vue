<template>
    <view>
        <u-popup
            v-model="showPopup"
            mode="bottom"
            border-radius="14"
            :mask-close-able="false"
            safe-area-inset-bottom
            closeable
            @close="emit('close')">
            <view class="my-[30rpx] px-[30rpx] flex flex-col justify-center">
                <view class="flex-1">
                    <view class="text-[36rpx]"> 绑定手机号 </view>
                    <!-- #ifndef MP-WEIXIN -->
                    <view class="pb-[100rpx] pt-[50rpx]">
                        <u-form borderBottom :label-width="150">
                            <u-form-item label="手机号" borderBottom>
                                <u-input
                                    class="flex-1"
                                    v-model="formData.mobile"
                                    :border="false"
                                    placeholder="请输入手机号码" />
                            </u-form-item>
                            <u-form-item label="验证码" borderBottom>
                                <u-input
                                    class="flex-1"
                                    v-model="formData.code"
                                    placeholder="请输入验证码"
                                    :border="false" />
                                <view
                                    class="border-l border-solid border-0 border-light pl-3 text-muted leading-4 ml-3 w-[180rpx]"
                                    @click="sendSms">
                                    <u-verification-code
                                        ref="uCodeRef"
                                        :seconds="60"
                                        @change="codeChange"
                                        change-text="x秒" />
                                    {{ codeTips }}
                                </view>
                            </u-form-item>
                        </u-form>
                    </view>
                    <!-- #endif -->
                    <!-- #ifdef MP-WEIXIN -->
                    <view class="py-[100rpx] flex flex-col items-center">
                        <view class="text-[36rpx]"> 绑定手机号 </view>
                        <view class="text-muted mt-[20rpx]"> 绑定手机号，以获取更好的体验 </view>
                    </view>

                    <!-- #endif -->
                </view>
                <view class="mt-[40rpx]">
                    <!-- #ifndef MP-WEIXIN -->
                    <u-button type="primary" shape="circle" @click="handleConfirm"> 确定 </u-button>
                    <!-- #endif -->
                    <!-- #ifdef MP-WEIXIN -->
                    <u-button
                        type="primary"
                        shape="circle"
                        hover-class="none"
                        open-type="getPhoneNumber"
                        @getphonenumber="getPhoneNumber">
                        <!--                        <u-icon name="weixin-fill" size="40" />-->
                        <text class="ml-[10rpx]"> 授权手机号</text>
                    </u-button>
                    <!-- #endif -->
                </view>
            </view>
        </u-popup>
    </view>
</template>

<script lang="ts" setup>
import { userBindMobile, userMnpMobile } from "@/api/user";
import { smsSend } from "@/api/app";
import { SMSEnum } from "@/enums/appEnums";
import { computed, reactive, ref, shallowRef } from "vue";
import { useUserStore } from "@/stores/user";

const props = defineProps({
    show: {
        type: Boolean,
    },
    userInfo: {
        type: Object,
    },
});
const emit = defineEmits<{
    (event: "update:show", show: boolean): void;
    (event: "success"): void;
    (event: "close"): void;
}>();

const showPopup = computed({
    get() {
        return props.show;
    },
    set(val) {
        emit("update:show", val);
    },
});
const uCodeRef = shallowRef();
const codeTips = ref("");

const userStore = useUserStore();
const codeChange = (text: string) => {
    codeTips.value = text;
};

const formData = reactive({
    type: "bind",
    mobile: "",
    code: "",
});
const sendSms = async () => {
    if (!formData.mobile) return uni.$u.toast("请输入手机号码");
    if (uCodeRef.value?.canGetCode) {
        await smsSend({
            scene: SMSEnum.BIND_MOBILE,
            mobile: formData.mobile,
        });
        uni.$u.toast("发送成功");
        uCodeRef.value?.start();
    }
};
const handleConfirm = async () => {
    if (!formData.mobile) return uni.$u.toast("请输入手机号码");
    if (!formData.code) return uni.$u.toast("请输入验证码");
    await userBindMobile(formData, { token: props.userInfo?.token });
    uni.$u.toast("绑定成功");
    emit("success");
};

const getPhoneNumber = async (e: any): Promise<void> => {
    const { encryptedData, iv, code } = e.detail;
    const data = {
        code,
        encrypted_data: encryptedData,
        iv,
    };
    if (encryptedData) {
        try {
            await userMnpMobile(
                {
                    ...data,
                },
                { token: props.userInfo?.token }
            );
            uni.$u.toast("绑定成功");
            emit("success");
        } catch (error) {
            uni.$u.toast(error || "绑定失败");
        }
    }
};
</script>

<style lang="scss" scoped></style>
