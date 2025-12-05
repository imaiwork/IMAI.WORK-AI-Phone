<template>
    <ElPopover trigger="click" width="222px" popper-class="!border-none !rounded-2xl !p-0" :show-arrow="false">
        <template #reference>
            <div class="w-10 h-10 p-[2px] rounded-full cursor-pointer">
                <div
                    class="w-full h-full rounded-full flex items-center justify-center border border-[#FFBC50] p-[2px]">
                    <img :src="userInfo.avatar" class="w-full h-full rounded-full object-cover" />
                </div>
            </div>
        </template>
        <div class="px-4 py-[18px]">
            <div class="flex flex-col gap-y-2 text-xs">
                <div class="flex items-center gap-x-2">
                    用户名：<span class="text-[#00000080]">{{ userInfo.nickname }}</span>
                    <ElTooltip content="复制">
                        <span class="cursor-pointer" @click="copy(userInfo.nickname)">
                            <Icon name="local-icon-copy"></Icon>
                        </span>
                    </ElTooltip>
                </div>
                <div class="flex items-center gap-x-2">
                    ID：<span class="text-[#00000080]">{{ userInfo.sn }}</span>
                    <ElTooltip content="复制">
                        <span class="cursor-pointer" @click="copy(userInfo.sn)">
                            <Icon name="local-icon-copy"></Icon>
                        </span>
                    </ElTooltip>
                </div>
                <div class="flex items-center gap-x-2">
                    手机号：<span class="text-[#00000080]">{{ userInfo.mobile }}</span>
                    <ElTooltip content="复制">
                        <span class="cursor-pointer" @click="copy(userInfo.mobile)">
                            <Icon name="local-icon-copy"></Icon>
                        </span>
                    </ElTooltip>
                </div>
                <div>
                    注册时间：<span class="text-[#00000080]">{{
                        dayjs(userInfo.create_time).format("YYYY-MM-DD")
                    }}</span>
                </div>
            </div>
            <div class="my-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="2" viewBox="0 0 272 2" fill="none">
                    <path opacity="0.1" d="M0 1L272 0.999976" stroke="black" stroke-width="0.5" />
                </svg>
            </div>
            <div class="">
                <router-link to="/creation">
                    <div class="util-menu">
                        <div class="wrapper group">
                            <div class="icon">
                                <Icon name="local-icon-menu_creation"></Icon>
                            </div>
                            <div class="font-bold">创作记录</div>
                        </div>
                    </div>
                </router-link>
                <div class="util-menu" @click="openBase">
                    <div class="wrapper group">
                        <div class="flex items-center gap-x-3">
                            <div class="icon">
                                <Icon name="local-icon-color_switch"></Icon>
                            </div>
                            <div class="font-bold">政策协议</div>
                        </div>
                    </div>
                </div>
                <div class="util-menu" @click="quit()">
                    <div class="wrapper group">
                        <div class="icon">
                            <Icon name="local-icon-logout"></Icon>
                        </div>
                        <div class="font-bold">退出登录</div>
                    </div>
                </div>
            </div>
        </div>
    </ElPopover>
    <base-popup v-if="showBasePop" ref="basePopupRef" @close="showBasePop = false"></base-popup>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import dayjs from "dayjs";

const emit = defineEmits(["recharge"]);
const userStore = useUserStore();

const { userInfo } = toRefs(userStore);

const showLogoutPopup = ref(false);

const showBasePop = ref(false);
const basePopupRef = shallowRef();
const openBase = async () => {
    showBasePop.value = true;
    await nextTick();
    basePopupRef.value?.open();
    showBasePop.value = true;
    await nextTick();
    basePopupRef.value?.open();
};

const quit = async () => {
    useNuxtApp().$confirm({
        title: "确定退出登录吗？",
        message: "退出登录不会丢失任何数据，你仍可以登录此账号。",
        onConfirm: () => {
            showLogoutPopup.value = false;
            userStore.logout();
            window.location.reload();
        },
    });
};

const { copy } = useCopy();
</script>

<style scoped lang="scss">
.util-menu {
    @apply h-11 cursor-pointer rounded-md border border-[transparent] hover:border-token-primary p-[1px];
    .wrapper {
        @apply rounded-md flex items-center gap-x-3 px-[10px] h-full hover:bg-[#fbfbfb] hover:text-primary;
        .icon {
            @apply w-5 h-5 rounded bg-[#0000000d] flex items-center justify-center group-hover:bg-primary-light-9;
        }
    }
}
</style>
