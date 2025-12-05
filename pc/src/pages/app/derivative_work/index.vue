<template>
    <div class="h-full flex">
        <ElAside width="300px">
            <SidebarConfig @success="resetPage()" />
        </ElAside>
        <div class="px-8 grow h-full relative">
            <div class="h-full flex flex-col pb-[120px]">
                <div class="pt-8 w-full h-full flex flex-col">
                    <div>音乐生成记录</div>
                    <ElDivider class="!my-5 !mb-0" />
                    <div>
                        <ElTabs>
                            <ElTabPane label="全部"></ElTabPane>
                            <!-- <ElTabPane label="已完成"></ElTabPane>
							<ElTabPane label="进行中"></ElTabPane>
							<ElTabPane label="生成失败"></ElTabPane> -->
                        </ElTabs>
                    </div>
                    <div class="grow min-h-0" v-loading="pager.loading">
                        <div class="h-full flex flex-col" v-if="pager.lists.length">
                            <div class="h-full grow min-h-0 relative">
                                <div class="min-w-[700px] flex h-full">
                                    <div class="bg-[#FAFAFA] h-full grow">
                                        <ElScrollbar>
                                            <div class="space-y-4 p-5">
                                                <div
                                                    class="group cursor-pointer bg-white border border-[transparent] rounded-lg p-3 relative shadow-[1px_2px_6px_0px_rgba(0,0,0,0.08)] flex justify-between"
                                                    :class="{
                                                        'border border-primary !bg-primary-light-9':
                                                            activeMusic == index,
                                                    }"
                                                    v-for="(item, index) in getMusicList"
                                                    :key="index"
                                                    @click="chooseMusic(index)">
                                                    <template v-if="item.status == 2">
                                                        <div class="flex items-center gap-4 grow">
                                                            <div>
                                                                <ElImage
                                                                    :src="item.image_url"
                                                                    class="h-16 w-16 rounded-full"></ElImage>
                                                            </div>
                                                            <div class="flex flex-col gap-2 grow">
                                                                <div class="line-clamp-1">
                                                                    {{ item.ask }}
                                                                </div>
                                                                <div
                                                                    class="flex justify-between items-end w-full text-tx-primary text-sm">
                                                                    <div class="flex flex-col gap-1">
                                                                        <span>{{ item.tags }}</span>
                                                                        <span class="text-xs">{{
                                                                            formatAudioTime(item.duration)
                                                                        }}</span>
                                                                    </div>
                                                                    <div class="text-xs">
                                                                        {{ item.create_time }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="ml-2 h-full invisible group-hover:visible absolute right-2"
                                                            :class="[hoverMusic == item ? '!visible' : '']"
                                                            @click.stop>
                                                            <ElPopover
                                                                :width="90"
                                                                @hide="hoverMusic = 0"
                                                                @show="hoverMusic = item">
                                                                <template #reference>
                                                                    <div class="rotate-90 origin-center p-1">
                                                                        <Icon name="el-icon-MoreFilled"></Icon>
                                                                    </div>
                                                                </template>
                                                                <div class="flex flex-col text-center gap-y-2">
                                                                    <div>
                                                                        <ElButton
                                                                            link
                                                                            class="!m-0"
                                                                            size="small"
                                                                            @click="exportFile(item.audio_url)">
                                                                            导出文件
                                                                        </ElButton>
                                                                    </div>
                                                                    <div>
                                                                        <ElButton
                                                                            type="danger"
                                                                            link
                                                                            size="small"
                                                                            @click="handleDelete(item.id, index)"
                                                                            >删除</ElButton
                                                                        >
                                                                    </div>
                                                                </div>
                                                            </ElPopover>
                                                        </div>
                                                    </template>
                                                    <div class="h-full flex items-center justify-center w-full" v-else>
                                                        <img src="@/assets/images/turn_loading.png" class="h-10" />
                                                        <span class="absolute right-2 top-2">
                                                            <ElTag type="danger">生成中</ElTag>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </ElScrollbar>
                                    </div>
                                    <div
                                        class="shadow rounded-xl bg-white z-50 h-full w-[40%]"
                                        style="
                                            background: linear-gradient(
                                                137.64deg,
                                                rgba(184, 219, 255, 1) 0%,
                                                rgba(255, 255, 255, 1) 100%
                                            );
                                        ">
                                        <div class="flex flex-col items-center p-5 h-full min-h-0" v-if="currMusic">
                                            <div>
                                                <ElImage
                                                    :src="currMusic.image_url"
                                                    class="w-[195px] h-[195px] rounded-full"></ElImage>
                                            </div>
                                            <div class="text-center mt-4">
                                                <div class="text-lg">
                                                    {{ currMusic.ask }}
                                                </div>
                                                <div class="text-tx-primary text-sm mt-2">
                                                    {{ currMusic.tags }}
                                                </div>
                                            </div>
                                            <div class="grow min-h-0 mt-4 w-full">
                                                <ElScrollbar>
                                                    <div class="px-2 whitespace-pre-line flex justify-center">
                                                        {{ currMusic.lyric }}
                                                    </div>
                                                </ElScrollbar>
                                            </div>
                                        </div>
                                        <div v-else class="h-full flex items-center justify-center">
                                            <ElEmpty description="暂无音乐数据" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="h-full flex items-center justify-center" v-else>
                            <ElEmpty :image="EmptyImage" description="暂无音乐记录，赶紧去创作吧~"></ElEmpty>
                        </div>
                    </div>
                </div>
            </div>
            <div
                class="h-24 bg-white w-full shadow-[0_-20px_20px_0_rgba(0,0,0,0.02)] absolute z-50 bottom-0 left-0"
                v-if="pager.lists.length">
                <div class="h-full flex items-center px-5">
                    <div class="flex items-center gap-4 w-[25%]">
                        <div>
                            <ElImage class="h-16 w-16 rounded-full" :src="currMusic.image_url"></ElImage>
                        </div>
                        <div class="flex flex-col grow">
                            <div>{{ currMusic.ask }}</div>
                            <div class="text-sm text-tx-primary">
                                <span>{{ currMusic.tags }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="grow ml-14">
                        <div>
                            <MusicControl
                                :music="currMusic.audio_url"
                                ref="musicControlRef"
                                @next="nextMusic"
                                @prev="prevMusic" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { musicLists, musicDelete } from "@/api/music";
import { ToolEnum } from "@/enums/appEnums";
import SidebarConfig from "./_components/sidebar.vue";
import MusicControl from "./_components/control.vue";
import EmptyImage from "@/assets/images/empty_image.png";
import { formatAudioTime, downloadFile } from "@/utils/util";

const { pager, getLists, resetPage } = usePaging({
    size: 25000,
    fetchFun: musicLists,
});

const musicControlRef = shallowRef<InstanceType<typeof MusicControl>>(null);
const hoverMusic = ref<number>(0);
const activeMusic = ref<number>(0);

const currMusic = computed(() => {
    return getMusicList.value[activeMusic.value] || {};
});

const getMusicList = computed(() => {
    const { lists } = pager;
    if (lists.length) {
        const data = lists.map((item: any) => {
            const jsonArr = Object.keys(item.json_info);
            const result = jsonArr.length ? formatMusicResult(item.json_info[jsonArr[0]]) : {};
            return {
                ...item,
                ...result,
            };
        });
        return data;
    }
});

const formatMusicResult = (value: any) => {
    if (!value) return {};
    const { prompt = {}, image_url, audio_url, status } = value;
    return {
        image_url,
        audio_url,
        status,
        tags: prompt.tags?.replace(/\s+/g, "；"),
        duration: prompt.duration,
        lyric: prompt.prompt,
    };
};

const chooseMusic = (index: number) => {
    activeMusic.value = index;
};

const nextMusic = () => {
    if (activeMusic.value < getMusicList.value.length - 1) {
        activeMusic.value++;
    } else {
        activeMusic.value = 0;
    }
    musicControlRef.value?.resetPlayer();
};

const prevMusic = () => {
    if (activeMusic.value > 0) {
        activeMusic.value--;
    } else {
        activeMusic.value = getMusicList.value.length - 1;
    }
    musicControlRef.value?.resetPlayer();
};

const exportFile = (url: string) => {
    feedback.loading("保存中");
    downloadFile(url)
        .then(() => {
            feedback.closeLoading();
            feedback.msgSuccess("导出文件成功");
        })
        .catch(() => {
            feedback.closeLoading();
            feedback.msgError("导出文件失败");
        });
};

const handleDelete = async (id: number, index: number) => {
    await feedback.confirm("此操作不可逆，确定要删除吗？");
    await musicDelete({ id });
    getLists();
    activeMusic.value = 0;
    musicControlRef.value?.resetPlayer();
};

const handleExport = async (url: string) => {
    try {
    } catch (error) {
        feedback.msgError("下载音乐时出错");
    }
};

onMounted(() => {
    getLists();
});
</script>

<style scoped lang="scss">
:deep() {
    .el-tabs {
        .el-tabs__nav-wrap {
            &::after {
                display: none;
            }
        }
    }
}
</style>
