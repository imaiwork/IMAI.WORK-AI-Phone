<template>
    <ElPopover
        trigger="click"
        width="260px"
        popper-class="!p-0 !rounded-[20px] !border-none !shadow-light"
        :show-arrow="false"
        placement="bottom-end"
        :offset="12">
        <template #reference>
            <div class="avatar-wrapper group">
                <div class="avatar-ring border-primary">
                    <img :src="userInfo.avatar" class="w-full h-full rounded-full object-cover shadow-inner" />
                </div>
            </div>
        </template>

        <div class="overflow-hidden">
            <div class="p-5 bg-gradient-to-br from-[#F8FAFC] to-white border-b border-[#F1F5F9]">
                <div class="flex items-center gap-3 mb-4">
                    <img :src="userInfo.avatar" class="w-12 h-12 rounded-xl object-cover shadow-md" />
                    <div class="flex flex-col">
                        <span class="font-[900] text-[#0F172A] text-base leading-tight">{{ userInfo.nickname }}</span>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="text-[11px] text-[#94A3B8] font-bold">SN: {{ userInfo.sn }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="info-row group" @click="copy(userInfo.mobile)">
                        <div class="flex items-center gap-2">
                            <Icon name="el-icon-Phone" :size="12" color="#94A3B8" />
                            <span class="text-[12px] font-bold text-[#64748B]">{{ userInfo.mobile }}</span>
                        </div>
                        <span class="text-[#CBD5E1] group-hover:text-primary"><Icon name="local-icon-copy" /></span>
                    </div>
                    <div class="info-row">
                        <div class="flex items-center gap-2 text-[11px] font-bold text-[#CBD5E1]">
                            <Icon name="el-icon-Calendar" :size="12" />
                            <span>注册于 {{ dayjs(userInfo.create_time).format("YYYY-MM-DD") }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-2 space-y-1">
                <router-link to="/creation" class="menu-link group">
                    <div class="menu-icon bg-[#F1F5F9] group-hover:bg-primary-light-9">
                        <span class="text-[#CBD5E1] group-hover:text-primary"
                            ><Icon name="local-icon-menu_creation"
                        /></span>
                    </div>
                    <span class="menu-text">创作记录</span>
                    <span class="ml-auto opacity-0 group-hover:opacity-100 text-[#CBD5E1]"
                        ><Icon name="el-icon-ArrowRight" :size="12"
                    /></span>
                </router-link>

                <div class="menu-link group" @click="openBase">
                    <div class="menu-icon bg-[#F1F5F9] group-hover:bg-primary-light-9">
                        <span class="text-[#CBD5E1] group-hover:text-primary"
                            ><Icon name="local-icon-color_switch"
                        /></span>
                    </div>
                    <span class="menu-text">服务协议与政策</span>
                </div>

                <div class="h-[1px] bg-[#F1F5F9] my-1 mx-2"></div>

                <div class="menu-link group logout-item" @click="quit()">
                    <div class="menu-icon bg-[#FEF2F2] group-hover:!bg-[#EF4444]">
                        <span class="text-[#EF4444] group-hover:text-white">
                            <Icon name="local-icon-logout" />
                        </span>
                    </div>
                    <span class="menu-text group-hover:!text-[#EF4444]">退出当前账号</span>
                </div>
            </div>
        </div>
    </ElPopover>

    <base-popup v-if="showBasePop" ref="basePopupRef" @close="showBasePop = false" />
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
.avatar-wrapper {
    @apply w-10 h-10 p-[2px] transition-transform active:scale-95 cursor-pointer;
    .avatar-ring {
        @apply w-full h-full rounded-full flex items-center justify-center border-2 p-[2px] transition-all group-hover:rotate-12 group-hover:border-primary;
    }
}

.info-row {
    @apply flex items-center justify-between px-2 py-1.5 rounded-lg cursor-pointer transition-colors hover:bg-white;
}

.menu-link {
    @apply flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer transition-all;
    &:hover {
        @apply bg-[#F1F6FF];
        .menu-text {
            @apply text-primary;
        }
    }

    .menu-icon {
        @apply w-8 h-8 rounded-lg flex items-center justify-center transition-all;
    }

    .menu-text {
        @apply text-[13px] font-bold text-[#475569] transition-colors;
    }
}

.logout-item:hover {
    @apply bg-[#FEF2F2];
}
</style>
