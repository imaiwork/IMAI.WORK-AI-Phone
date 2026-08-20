<template>
    <div class="p-4 flex gap-[10px] h-full">
        <Sidebar
            :sidebar="sidebar"
            :sidebarIndex="sidebarIndex"
            :theme="ThemeEnum.LIGHT"
            @update:sidebarIndex="getSliderIndex" />
        <div class="grow overflow-hidden">
            <component :is="getComponents"></component>
        </div>
    </div>
</template>

<script setup lang="ts">
import { knowledgeBaseDetail, vectorKnowledgeBaseDetail } from "@/api/knowledge_base";
import { ThemeEnum } from "@/enums/appEnums";
import Sidebar from "@/pages/app/_components/sidebar.vue";
import useSidebar from "@/pages/app/_hooks/useSidebar";
import { KnTypeEnum, SidebarTypeEnum } from "../_enums";
import Content from "./_pages/content/index.vue";
import HitTest from "./_pages/hit_test/index.vue";
import Setting from "./_pages/setting/index.vue";

const route = useRoute();
const query = searchQueryToObject();

/** 是否可编辑知识库(创建者/协作者编辑权);团队共享查看者为 false */
const kbCanManage = ref(true);
provide("kbCanManage", kbCanManage);

const { sidebar, sidebarIndex, getComponents, residentParams, getSliderIndex } = useSidebar();

const allSidebar = [
    {
        name: "文档内容",
        type: SidebarTypeEnum.CONTENT,
        components: markRaw(Content),
        icon: "menu_content",
    },
    {
        name: "搜索测试",
        type: SidebarTypeEnum.HIT_TEST,
        components: markRaw(HitTest),
        icon: "menu_search",
    },
    {
        name: "设置",
        type: SidebarTypeEnum.SETTING,
        components: markRaw(Setting),
        icon: "menu_setting",
    },
];

sidebar.value = [...allSidebar];

const syncSidebarByPermission = () => {
    sidebar.value = kbCanManage.value
        ? [...allSidebar]
        : allSidebar.filter((item) => item.type !== SidebarTypeEnum.SETTING);
    // 若当前停在设置页且无权限,回退到文档内容
    if (!kbCanManage.value && Number(sidebarIndex.value) === SidebarTypeEnum.SETTING) {
        getSliderIndex(SidebarTypeEnum.CONTENT);
    }
};

const loadPermission = async () => {
    const id = route.params.id;
    if (!id) return;
    try {
        const isRag = String(route.query.kn_type) === KnTypeEnum.RAG;
        const detail = isRag
            ? await knowledgeBaseDetail({ id })
            : await vectorKnowledgeBaseDetail({ id });
        // 向量库:owned=1 或 power<=2(全部/编辑);RAG:is_owner/is_super
        if (isRag) {
            kbCanManage.value = Number(detail.is_owner) === 1 || Number(detail.is_super) === 1;
        } else {
            const power = Number(detail.power);
            kbCanManage.value =
                Number(detail.owned) === 1 || Number(detail.is_owner) === 1 || (power > 0 && power < 3);
        }
    } catch {
        // 详情失败时保守隐藏管理入口
        kbCanManage.value = false;
    }
    syncSidebarByPermission();
};

watch(
    () => route.query,
    () => {
        residentParams.value = {
            kn_type: query.kn_type,
            kn_name: query.kn_name,
            index_id: query.index_id || undefined,
            category_id: query.category_id || undefined,
        };
    },
    { immediate: true }
);

watch(
    () => [route.params.id, route.query.kn_type],
    () => {
        loadPermission();
    },
    { immediate: true }
);

definePageMeta({
    layout: "base",
});
</script>

<style scoped></style>
