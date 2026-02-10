<template>
    <div class="h-full flex flex-col bg-slate-50">
        <div class="sticky top-0 z-20 bg-slate-50/80 backdrop-blur-md px-6 py-4">
            <ElScrollbar>
                <div class="flex items-center gap-3">
                    <div
                        v-for="item in categoryLists"
                        :key="item.type"
                        class="px-5 py-2 rounded-full cursor-pointer whitespace-nowrap text-[13px] font-black transition-all duration-300"
                        :class="[
                            sceneType == item.type
                                ? 'bg-primary text-white  shadow-[#0065fb]/20'
                                : 'bg-white text-[#94A3B8] border border-[#F1F5F9] hover:text-primary hover:border-[#0065fb]/30',
                        ]"
                        @click="handleSceneType(item.type)">
                        {{ item.name }}
                    </div>
                </div>
            </ElScrollbar>
        </div>

        <div class="grow min-h-0">
            <ElScrollbar :distance="20" @end-reached="load">
                <div class="p-4">
                    <template v-if="pager.lists.length">
                        <div class="grid grid-cols-2 gap-5 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                            <div v-for="(item, index) in pager.lists" :key="index" class="record-card group">
                                <div class="relative aspect-[3/4] overflow-hidden rounded-[20px] bg-[#F1F5F9]">
                                    <div
                                        class="absolute top-2.5 right-2.5 z-10 px-2 py-1 rounded-[6px] bg-[#000000]/30 backdrop-blur-md border border-[#ffffff]/10 pointer-events-none">
                                        <span class="text-[10px] font-medium text-white tracking-wide">
                                            {{ getTypeName(item.draw_type || item.type) }}
                                        </span>
                                    </div>

                                    <div v-if="sceneType == DrawType.VIDEO" class="w-full h-full">
                                        <video :src="item.video_url" class="w-full h-full object-cover" muted></video>
                                        <div
                                            class="absolute top-3 left-3 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-[#000000]/20 backdrop-blur-md">
                                            <Icon name="el-icon-VideoPlay" color="#ffffff" :size="16"></Icon>
                                        </div>
                                    </div>

                                    <ElImage
                                        v-else
                                        :src="item.image"
                                        lazy
                                        class="w-full h-full transition-transform duration-500 group-hover:scale-110"
                                        fit="cover">
                                        <template #placeholder>
                                            <div class="flex items-center justify-center w-full h-full bg-slate-50">
                                                <div
                                                    class="w-8 h-8 border-2 border-[#0065fb] border-t-transparent rounded-full animate-spin"></div>
                                            </div>
                                        </template>
                                    </ElImage>

                                    <div
                                        class="absolute inset-0 bg-[#000000]/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center z-20">
                                        <div
                                            class="w-12 h-12 flex items-center justify-center rounded-full bg-[#ffffff]/20 backdrop-blur-lg border border-[#ffffff]/30 cursor-pointer hover:scale-110 transition-transform"
                                            @click="previewImage(item)">
                                            <Icon name="el-icon-View" color="#ffffff" :size="24"></Icon>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center mt-3 px-1">
                                    <div class="flex flex-col leading-tight">
                                        <span class="text-[11px] font-black text-[#64748B] tracking-tight">
                                            {{ item.create_time.split(" ")[0] }}
                                        </span>
                                        <span class="text-[10px] font-medium text-[#94A3B8] mt-0.5">
                                            {{ item.create_time.split(" ")[1] }}
                                        </span>
                                    </div>

                                    <div class="relative" @click.stop>
                                        <ElPopover
                                            :show-arrow="false"
                                            placement="top-end"
                                            popper-class="!p-1.5 !rounded-xl !border-[#F1F5F9] !min-w-[120px]"
                                            @show="visibleChange(true, item.id)"
                                            @hide="visibleChange(false, item.id)">
                                            <template #reference>
                                                <div
                                                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-[#F1F5F9] text-[#94A3B8] cursor-pointer transition-colors">
                                                    <Icon name="el-icon-MoreFilled" :size="16"></Icon>
                                                </div>
                                            </template>
                                            <div class="flex flex-col gap-1">
                                                <div
                                                    class="table-action-item hover:text-primary hover:bg-[#F5F7FF]"
                                                    @click="handleDownLoad(item)">
                                                    <Icon name="el-icon-Download" :size="14"></Icon>
                                                    <span>保存到本地</span>
                                                </div>
                                                <div
                                                    class="table-action-item hover:!text-danger hover:bg-[#FDE6E8]"
                                                    @click="handleDelete(item.id, index)">
                                                    <Icon name="el-icon-Delete" :size="14"></Icon>
                                                    <span>永久删除</span>
                                                </div>
                                            </div>
                                        </ElPopover>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <load-text :is-load="pager.isLoad"></load-text>
                    </template>

                    <template v-else>
                        <div class="flex flex-col items-center justify-center py-32 space-y-4">
                            <div
                                class="w-20 h-20 bg-white rounded-[24px] flex items-center justify-center border border-[#F1F5F9]">
                                <Icon name="local-icon-empty" :size="40" color="#CBD5E1"></Icon>
                            </div>
                            <span class="text-[13px] font-black text-[#94A3B8]">开启你的第一次艺术创作</span>
                        </div>
                    </template>
                </div>
            </ElScrollbar>
        </div>

        <ElImageViewer
            v-if="showPreview"
            :initial-index="0"
            :url-list="previewImages"
            @close="showPreview = false"></ElImageViewer>
        <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false"></preview-video>
    </div>
</template>

<script setup lang="ts">
import { drawingRecord, drawingVideoRecord, drawingVideoDelete, drawingDelete } from "@/api/drawing";
import { downloadFile } from "@/utils/util";
import { DrawTypeEnum, drawTypeEnumMap } from "@/pages/app/drawing/_enums";

enum DrawType {
    ALL = 0,
    GOODS = 1,
    FASHION = 2,
    TEXT_TO_IMAGE = 3,
    IMAGE_TO_IMAGE = 4,
    POSTER = 5,
    VIDEO = 6,
}

const sceneType = ref<number>(DrawType.ALL);
const activeImage = ref<any>("");

const categoryLists = computed(() => [
    { name: "全部", type: DrawType.ALL },
    { name: "文生图", type: DrawType.TEXT_TO_IMAGE },
    { name: "图生图", type: DrawType.IMAGE_TO_IMAGE },
    { name: "商品图", type: DrawType.GOODS },
    { name: "海报图", type: DrawType.POSTER },
    { name: "服饰图", type: DrawType.FASHION },
    { name: "视频记录", type: DrawType.VIDEO },
]);

const getTypeName = (type: string) => {
    const target = categoryLists.value.find((item) => item.type === parseInt(type));
    if (target) return target.name;

    if (sceneType.value === DrawType.VIDEO) return "视频生成";

    return "绘画";
};

const queryParams = reactive<any>({
    page_no: 1,
    type: sceneType.value,
    draw_type: sceneType.value,
});

const { pager, getLists, resetPage } = usePaging({
    size: 25,
    fetchFun: (params: any) => {
        if (sceneType.value == DrawType.VIDEO) {
            params.type = "";
        }
        return drawingRecord(params);
    },
    params: queryParams,
    isScroll: true,
});

const showPreview = ref(false);
const showPreviewVideo = ref(false);
const previewImages = ref<any[]>([]);
const previewVideoRef = shallowRef();

const previewImage = async (item: any) => {
    const isVideo = sceneType.value === DrawType.VIDEO;
    if (isVideo) {
        showPreviewVideo.value = true;
        await nextTick();
        previewVideoRef.value.open();
        previewVideoRef.value.setUrl(item.video_url);
    } else {
        showPreview.value = true;
        previewImages.value = [item.image];
    }
};

const visibleChange = (flag: boolean, id: number) => {
    activeImage.value = flag ? id : "";
};

const handleDownLoad = (item: any) => {
    const isVideo = sceneType.value === DrawType.VIDEO;
    const link = isVideo ? item.video_url : item.image;
    downloadFile(link);
};

const handleSceneType = (type: number) => {
    if (type === sceneType.value) return;
    sceneType.value = type;
    queryParams.page_no = 1;
    queryParams.draw_type = type;
    queryParams.type = type;
    resetPage();
};

const handleDelete = async (id: number, index: number) => {
    useNuxtApp().$confirm({
        message: "确定永久删除此记录吗？",
        onConfirm: async () => {
            try {
                const isVideo = sceneType.value === DrawType.VIDEO;
                if (isVideo) {
                    await drawingVideoDelete({ id });
                } else {
                    await drawingDelete({ log_id: id });
                }
                feedback.msgSuccess("删除成功");
                pager.lists.splice(index, 1);
            } catch (error) {
                feedback.msgError((error as string) || "删除失败");
            }
        },
    });
};

const load = async (e: string) => {
    if (e == "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no += 1;
        await getLists();
    }
};

onMounted(() => {
    getLists();
});
</script>

<style scoped lang="scss">
.record-card {
    @apply relative transition-all duration-300;
}

.record-card:hover video {
    filter: brightness(1.1);
}
</style>
