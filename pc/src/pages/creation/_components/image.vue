<template>
    <div class="h-full">
        <div class="flex items-center gap-2">
            <div
                v-for="item in categoryLists"
                :key="item.id"
                class="px-2 py-1 rounded-md cursor-pointer"
                :class="[
                    sceneType == item.type
                        ? 'text-[#000000] font-bold bg-[rgba(120,96,254,.08)]'
                        : 'bg-[rgba(139,95,95,0.04)]',
                ]"
                @click="handleSceneType(item.type)"
            >
                <span class="text-base">
                    {{ item.name }}
                </span>
            </div>
        </div>
        <div
            class="mt-4"
            v-infinite-scroll="load"
            :infinite-scroll-distance="10"
            :infinite-scroll-immediate="false"
            :infinite-scroll-disabled="!pager.isLoad"
        >
            <div class="pb-6" v-loading="pager.loading">
                <template v-if="pager.lists.length">
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                        <ElCard
                            class="cursor-pointer !rounded-xl !bg-[#f7f9ff] relative hover:scale-[1.02] !p-0"
                            shadow="never"
                            v-for="(item, index) in pager.lists"
                            :key="index"
                        >
                            <div class="flex flex-col rounded-lg w-full flex-grow gap-2 group relative">
                                <div class="flex items-center justify-center relative group">
                                    <video
                                        v-if="sceneType == drawTypeEnumMap[DrawTypeEnum.VIDEO_GENERATION]"
                                        :src="item.video_url"
                                        class="w-full h-44 object-cover"
                                    ></video>
                                    <ElImage v-else :src="item.image" lazy class="w-full h-44" fit="cover"></ElImage>
                                    <div
                                        class="bg-[rgba(0,0,0,0.35)] absolute z-40 w-full h-full rounded-lg invisible group-hover:visible flex items-center justify-center gap-2"
                                    >
                                        <div class="cursor-pointer flex gap-2 items-center" @click="previewImage(item)">
                                            <Icon name="el-icon-View" color="#ffffff" size="20"></Icon>
                                            <div class="text-white">预览</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center p-2">
                                    <div class="text-tx-primary text-sm">
                                        {{ item.create_time }}
                                    </div>
                                    <div
                                        class="invisible group-hover:visible"
                                        :class="[activeImage == item.id ? '!visible' : '']"
                                        @click.stop
                                    >
                                        <ElPopover
                                            :show-arrow="false"
                                            popper-class="!w-[120px] !min-w-[120px] !p-[6px] !rounded-xl"
                                            @show="visibleChange(true, item.id)"
                                            @hide="visibleChange(false, item.id)"
                                        >
                                            <template #reference>
                                                <div class="rotate-90 origin-center p-1">
                                                    <Icon name="el-icon-MoreFilled"></Icon>
                                                </div>
                                            </template>
                                            <div class="flex flex-col gap-2">
                                                <div
                                                    class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                                    @click="handleDownLoad(item)"
                                                >
                                                    <Icon name="el-icon-Download"></Icon>
                                                    <span>下载</span>
                                                </div>
                                                <div
                                                    class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                                    @click="handleDelete(item.id, index)"
                                                >
                                                    <Icon name="el-icon-Delete"></Icon>
                                                    <span>删除记录</span>
                                                </div>
                                            </div>
                                        </ElPopover>
                                    </div>
                                </div>
                            </div>
                        </ElCard>
                    </div>
                    <div v-if="!pager.isLoad" class="text-center py-4 text-gray-500">暂无更多了</div>
                </template>
                <template v-else>
                    <div v-if="!pager.loading" class="mt-20">
                        <ElEmpty />
                    </div>
                </template>
            </div>
        </div>
        <ElImageViewer
            v-if="showPreview"
            :initial-index="0"
            :url-list="previewImages"
            @close="showPreview = false"
        ></ElImageViewer>
        <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false"></preview-video>
    </div>
</template>

<script setup lang="ts">
import { drawingRecord, drawingVideoRecord, drawingVideoDelete, drawingDelete } from "@/api/drawing";
import { downloadFile } from "@/utils/util";
import { DrawTypeEnum, drawTypeEnumMap } from "@/pages/app/drawing/_enums";

const sceneType = ref<number>(drawTypeEnumMap[DrawTypeEnum.GOODS_IMAGE]);
const activeImage = ref<any>("");

const categoryLists = computed(() => [
    { name: "商品图", id: 1, type: drawTypeEnumMap[DrawTypeEnum.GOODS_IMAGE] },
    { name: "服饰图", id: 2, type: drawTypeEnumMap[DrawTypeEnum.FASHION_IMAGE] },
    { name: "文生图", id: 3, type: drawTypeEnumMap[DrawTypeEnum.TXT2IMAGE] },
    { name: "图生图", id: 4, type: drawTypeEnumMap[DrawTypeEnum.IMAGE2IMAGE] },
    { name: "海报图", id: 5, type: drawTypeEnumMap[DrawTypeEnum.POSTER_IMAGE] },
    { name: "视频", id: 6, type: drawTypeEnumMap[DrawTypeEnum.VIDEO_GENERATION] },
]);

const queryParams = reactive<any>({
    page_no: 1,
    type: sceneType.value,
});

const fetchApi = (params: any) => {
    const isVideo = sceneType.value === drawTypeEnumMap[DrawTypeEnum.VIDEO_GENERATION];
    return isVideo ? drawingVideoRecord(params) : drawingRecord(params);
};

const { pager, getLists, resetPage } = usePaging({
    size: 25,
    fetchFun: fetchApi,
    params: queryParams,
    isScroll: true,
});

const showPreview = ref(false);
const showPreviewVideo = ref(false);
const previewImages = ref<any[]>([]);
const previewVideoRef = shallowRef();

const previewImage = async (item: any) => {
    const isVideo = sceneType.value === drawTypeEnumMap[DrawTypeEnum.VIDEO_GENERATION];
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
    const isVideo = sceneType.value === drawTypeEnumMap[DrawTypeEnum.VIDEO_GENERATION];
    const link = isVideo ? item.video_url : item.image;
    downloadFile(link);
};

const handleSceneType = (type: number) => {
    if (type === sceneType.value) return;
    sceneType.value = type;
    queryParams.page_no = 1;

    if (type === drawTypeEnumMap[DrawTypeEnum.VIDEO_GENERATION]) {
        delete queryParams.type;
    } else {
        queryParams.type = type;
    }
    resetPage();
};

const handleDelete = async (id: number, index: number) => {
    useNuxtApp().$confirm({
        message: "确定删除此条记录吗？",
        onConfirm: async () => {
            try {
                const isVideo = sceneType.value === drawTypeEnumMap[DrawTypeEnum.VIDEO_GENERATION];
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

const load = async () => {
    queryParams.page_no += 1;
    await getLists();
};

onMounted(() => {
    getLists();
});
</script>

<style scoped lang="scss">
:deep(.el-card__body) {
    padding: 0 !important;
}
</style>