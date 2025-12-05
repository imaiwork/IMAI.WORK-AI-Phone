<template>
    <div class="h-full flex flex-col min-w-[1000px]">
        <div class="h-[56px] rounded-lg bg-white flex items-center px-4 flex-shrink-0">
            <div class="flex items-center gap-4">
                <Icon name="local-icon-list_check" :size="18"></Icon>
                <span class="font-bold">社媒平台</span>
            </div>
            <div class="flex-1 flex items-center gap-8 ml-12">
                <div
                    v-for="item in socialPlatformList"
                    class="flex items-center gap-2 rounded-lg cursor-pointer hover:bg-[#F2F2F2] px-3 py-1.5"
                    :class="{ 'bg-[#F2F2F2]': currentSocialPlatform === item.type }"
                    :key="item.type"
                    @click="handleChangeSocialPlatform(item.type)">
                    <img :src="item.icon" :alt="item.name" class="w-6 h-6" />
                    <span>{{ item.name }}</span>
                </div>
            </div>
        </div>
        <div class="grow min-h-0 rounded-lg bg-white mt-4 flex">
            <div class="w-[94px] border-r border-[#D9D9D9] h-full">
                <AccountPanel />
            </div>
            <div class="w-[244px] h-full border-r border-[#D9D9D9]">
                <FriendsPanel />
            </div>
            <div class="flex-1">
                <ChattingPanel />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { AppTypeEnum, DeviceCmdEnum, DeviceCmdCodeEnum } from "@/enums/appEnums";
import AccountPanel from "./_components/account-panel.vue";
import FriendsPanel from "./_components/friends-panel.vue";
import ChattingPanel from "./_components/chatting-panel.vue";
import useAccount from "../../_hooks/useAccount";

const { socialPlatformList, currentSocialPlatform } = useSocialPlatform();

const handleChangeSocialPlatform = (type: AppTypeEnum) => {
    currentSocialPlatform.value = type;
};

const { accountLists, accountPager, getAccountList, currentAccount } = useAccount({
    type: currentSocialPlatform.value,
});

const { send, onEvent, isConnected } = useDeviceWs();

onEvent("success", (data: any) => {
    const { deviceId, type } = data;
    const DeviceHandlers = {
        [DeviceCmdEnum.INIT_COMPLETE]: async () => {
            updateDeviceStatus(deviceId);
        },
        [DeviceCmdEnum.CHECK_INIT]: async () => {
            updateDeviceStatus(deviceId);
        },
    } as const;

    const updateDeviceStatus = (deviceId: string) => {
        accountLists.value.forEach((item: any) => {
            if (item.device_code === deviceId) {
                item.status = 1;
                item.loading = false;
            }
        });
    };
    const handler = DeviceHandlers[type as keyof typeof DeviceHandlers];
    if (handler) {
        handler();
    }
});

onEvent("error", (err: any) => {
    const { error, code, deviceCode } = err;
    if (
        [
            DeviceCmdCodeEnum.CONNECT_ERROR,
            DeviceCmdCodeEnum.PUSH_MESSAGE_ERROR,
            DeviceCmdCodeEnum.DEVICE_DISCONNECTED,
            DeviceCmdEnum.CHECK_INIT,
        ].includes(code)
    ) {
        accountLists.value.forEach((item: any) => {
            if (code == DeviceCmdCodeEnum.CONNECT_ERROR || item.device_code == deviceCode) {
                item.status = 0;
                item.loading = false;
            }
        });
    }
    if (code != DeviceCmdCodeEnum.CONNECT_ERROR) {
        feedback.msgError(error);
    }
});

const init = async () => {
    await getAccountList();
    accountLists.value = accountPager.lists.map((item: any) => ({ ...item, status: 0, loading: true }));

    if (accountLists.value.length > 0 && isConnected.value) {
        send({
            type: DeviceCmdEnum.CHECK_INIT,
            deviceId: accountLists.value[0].device_code,
        });
    }
};

init();

watch(
    () => currentAccount.value,
    (val) => {}
);
</script>

<style scoped></style>
