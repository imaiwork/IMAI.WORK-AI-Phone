<template>
    <div
        class="h-full relative"
        :class="{
            'fixed top-0 left-0 w-full z-[8888]': isFullscreen,
        }">
        <template v-if="formData.reply">
            <div ref="toolbarContainer"></div>
            <svg ref="mindMapContainer" class="w-full h-full bg-white"></svg>
            <div class="absolute top-0 right-0 p-2">
                <ElButton type="primary" @click="handleExport">导出</ElButton>
            </div>
        </template>
        <template v-else>
            <div class="flex flex-col justify-center gap-4 items-center h-full">
                <img src="../../_assets/images/empty.png" class="w-[120px] h-[120px]" />
                <div class="text-[#585a73]">暂无脑图</div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { useMindMap } from "@/composables/useMindMap";

const props = defineProps<{
    detail: any;
}>();

const formData = reactive({
    reply: "",
});

const mindMapContainer = shallowRef<SVGSVGElement | null>(null);
const { toolbarContainer, isFullscreen, mindMapInit, mindMapFit, mindMapExportAsPNG } = useMindMap();

const handleExport = () => {
    mindMapExportAsPNG(mindMapContainer.value);
};

// 获取思维导图
const getMindMap = async () => {
    const { response } = props.detail;
    const lists = response?.Result?.Summarization?.Summarization?.MindMapSummary;
    const title = response?.Result?.Summarization?.Summarization?.ParagraphTitle;
    const markdown = convertToMarkdown({ title, lists });
    if (lists.length) {
        formData.reply = markdown;
        await nextTick();
        initMindMap();
    }
};

const initMindMap = async () => {
    mindMapInit(mindMapContainer.value, {
        color: (node: any) => "#2353f4",
        spacingVertical: 10,
        spacingHorizontal: 20,
    });
    await nextTick();
    mindMapFit(formData.reply);
};

function convertToMarkdown(data) {
    let markdown = "";
    if (data.lists.length) {
        data.lists.forEach((list) => {
            markdown += `# ${list.Title}\n\n`;
            list.Topic.forEach((topic) => {
                markdown += `### ${topic.Title}\n\n`;
                topic.Topic.forEach((subTopic) => {
                    markdown += `#### ${subTopic.Title}\n\n`;
                });
            });
        });
    }
    return markdown;
}

onMounted(() => {
    getMindMap();
});
</script>

<style scoped></style>
