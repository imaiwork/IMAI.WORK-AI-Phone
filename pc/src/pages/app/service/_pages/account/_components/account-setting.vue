<template>
    <div class="h-full flex flex-col w-full">
        <ElBreadcrumb class="mt-2">
            <ElBreadcrumbItem>
                <span class="cursor-pointer text-[#8A8C99] hover:text-primary" @click="emit('close')"> 账号管理 </span>
            </ElBreadcrumbItem>
            <ElBreadcrumbItem>账号设置</ElBreadcrumbItem>
        </ElBreadcrumb>
        <div class="grow min-h-0 bg-white rounded-lg mt-4 flex flex-col pt-4">
            <div class="px-4">
                <ElTabs v-model="activeTab">
                    <ElTabPane :name="item.name" v-for="item in tabs" :key="item.name">
                        <template #label>
                            <div class="flex items-center gap-x-2">
                                <Icon :name="item.icon"></Icon>
                                <span>{{ item.label }}</span>
                            </div>
                        </template>
                    </ElTabPane>
                </ElTabs>
            </div>
            <div class="grow min-h-0 mt-4">
                <BaseSetting v-if="activeTab === 'base-setting'"></BaseSetting>
                <!-- <PublishSetting v-if="activeTab === 'publish-setting'"></PublishSetting> -->
                <AutoAddWechatSetting v-if="activeTab === 'auto-add-wechat'"></AutoAddWechatSetting>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import BaseSetting from "./base-setting.vue";
import PublishSetting from "./publish-setting.vue";
import AutoAddWechatSetting from "./auto-add-wechat.vue";
const emit = defineEmits<{
    (e: "close"): void;
}>();

const activeTab = ref("base-setting");
const tabs = [
    {
        label: "基础设置",
        name: "base-setting",
        icon: "local-icon-setting",
    },
    // {
    //     label: "名片推送设置",
    //     name: "publish-setting",
    //     icon: "local-icon-id_card",
    // },
    {
        label: "自动加微信设置",
        name: "auto-add-wechat",
        icon: "local-icon-wechat3",
    },
];
</script>

<style scoped></style>
