<template>
    <u-popup
        v-model="show"
        mode="bottom"
        :custom-style="{ backgroundColor: 'transparent', padding: '0 20rpx' }"
        :height="getPopupHeight"
        :mask="false"
        @close="close">
        <view class="pb-[32rpx] flex flex-col h-full bg-white rounded-xl relative">
            <view class="absolute top-5 right-4">
                <view @click="close">
                    <u-icon name="close" size="32"></u-icon>
                </view>
            </view>
            <view class="grow min-h-0 flex flex-col">
                <view class="my-4 text-center text-[#022541] font-bold"> 补充您要生成的信息 </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-[32rpx]">
                            <form-designer ref="formRef" :formLists="formList" v-model="formData"></form-designer>
                        </view>
                        <view :style="{ height: dynamicHeight + 'px' }"></view>
                    </scroll-view>
                </view>
            </view>
            <view class="px-[32rpx] mt-4">
                <u-button type="primary" :custom-style="{ borderRadius: '16rpx' }" @click="submit"
                    >直接发送提问
                </u-button>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import FormDesigner from "@/packages/components/form-designer/form-designer.vue";
import { TokensSceneEnum } from "@/enums/appEnums";
import useKeyboardHeight from "@/hooks/useKeyboardHeight";

const props = withDefaults(
    defineProps<{
        formList: any[];
        title: string;
    }>(),
    {
        formList: () => [],
        title: "",
    }
);
const emit = defineEmits(["open", "close", "success"]);

const { dynamicHeight } = useKeyboardHeight();

const getPopupHeight = computed(() => {
    const { windowHeight, statusBarHeight } = uni.$u.sys();
    let menuButton = {
        height: 0,
        top: 0,
    };
    //#ifdef MP-WEIXIN
    menuButton = uni.getMenuButtonBoundingClientRect();
    //#endif
    const navbarHeight = menuButton.height + (menuButton.top - statusBarHeight);

    return `${windowHeight - statusBarHeight - navbarHeight - 40}px`;
});

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const getSceneTokens = userStore.getTokenByScene(TokensSceneEnum.SCENE_CHAT)?.score;

const show = ref(false);

const formData = reactive<any>({});
const formRef = shallowRef();

const submit = async () => {
    if (userTokens.value <= 0) {
        uni.$u.toast("算力不足，请充值！");
        return;
    }
    await formRef.value?.validate();
    emit("success", formData);
    close();
};

const open = () => {
    show.value = true;
};

const close = () => {
    show.value = false;
    emit("close");
};

const setFormData = () => {
    props.formList.forEach((item: any) => {
        formData[item.props.field] = "";
    });
};

watchEffect(() => {
    setFormData();
});

defineExpose({
    open,
    close,
    formData,
});
</script>

<style scoped></style>
