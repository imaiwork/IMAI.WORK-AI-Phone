<template>
    <div>
        <el-card shadow="never" class="!border-none">
            <el-tabs v-model="activeTab">
                <el-tab-pane
                    v-for="(item, index) in tabLists"
                    :label="`${item.name}`"
                    :name="item.label"
                    :key="index"
                    lazy>
                </el-tab-pane>
            </el-tabs>
            <div class="mx-[10px] mb-[20px] flex flex-wrap gap-2">
                <div
                    v-for="item in currentModel"
                    :key="item.id"
                    class="flex items-center px-[15px] py-[25px] w-[300px] bg-[#f8f8f8] rounded-[12px] h-full hover:bg-primary-light-9 border border-solid border-page hover:border-primary">
                    <div
                        class="flex items-center flex-1 min-w-0"
                        :class="{
                            'opacity-60': !item.is_enable,
                        }">
                        <el-image :src="item.logo" class="w-[44px] h-[44px]" v-if="item.logo" />
                        <div class="mx-[16px] flex-1 min-w-0">
                            <div class="text-xl font-bold mb-[4px]">{{ item.name }}</div>
                            <div
                                class="flex items-center text-tx-secondary before:mr-[6px] before:block before:w-[8px] before:h-[8px] before:bg-success before:rounded-[50%]"
                                :class="{
                                    'before:!bg-danger': !item.is_enable,
                                }">
                                {{ item.is_enable ? "已启用" : "已停用" }}
                            </div>
                        </div>
                    </div>
                    <router-link
                        v-perms="['ai_setting.ai_models/edit']"
                        :to="{
                            path: getRoutePath('ai_setting.ai_models/edit'),
                            query: {
                                id: item.id,
                                type: item.type,
                            },
                        }">
                        <el-button type="primary" plain>编辑</el-button>
                    </router-link>
                </div>
            </div>
        </el-card>
    </div>
</template>
<script setup lang="ts">
import Session from "./components/session.vue";
import Embedding from "./components/embedding.vue";
import { getAiModel } from "@/api/ai_setting/model";
import { getRoutePath } from "@/router";
const activeTab = ref("chatModels");
const tabLists = reactive([
    {
        name: "语言模型设置",
        label: "chatModels",
        component: shallowRef(Session),
        type: 1,
    },
    {
        name: "生图模型设置",
        label: "drawModels",
        component: shallowRef(Embedding),
        type: 3,
    },
    {
        name: "数字人模型设置",
        label: "humanModels",
        component: shallowRef(Embedding),
        type: 4,
    },
]);

const configLists = ref<any>({
    chatModels: [],
    vectorModels: [],
});
const currentModel = computed({
    get() {
        return configLists.value[activeTab.value];
    },
    set(value) {
        configLists.value[activeTab.value] = value;
    },
});
const getData = async () => {
    configLists.value = await getAiModel();
};

getData();
</script>
