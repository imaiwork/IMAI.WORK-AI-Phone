<template>
    <div class="h-full flex flex-col" v-if="!showManage">
        <div class="bg-white rounded-xl h-[107px] flex justify-between items-center px-[30px] flex-shrink-0">
            <div>
                <div class="text-2xl">机器人列表</div>
                <div class="text-[#74798C] mt-1">创建你的机器人，接替对应的客服岗位</div>
            </div>
            <div>
                <ElButton type="primary" @click="handleCreateRobot">创建机器人</ElButton>
            </div>
        </div>
        <div
            class="grow min-h-0 flex flex-col mt-4 overflow-y-auto"
            :infinite-scroll-immediate="false"
            :infinite-scroll-disabled="!pager.isLoad"
            :infinite-scroll-distance="10"
            v-infinite-scroll="load">
            <template v-if="pager.lists.length > 0">
                <div class="grid grid-cols-3 gap-6 pb-4">
                    <div
                        v-for="(item, index) in pager.lists"
                        :key="index"
                        class="h-[168px] rounded-xl bg-white px-6 py-4 group relative cursor-pointer flex flex-col"
                        @click="handleManage(item.id)">
                        <div class="flex items-center gap-4">
                            <ElImage :src="item.logo" lazy fit="cover" class="w-10 h-10 rounded-[5px]" />
                            <span class="text-lg">{{ item.name }}</span>
                        </div>
                        <div class="grow">
                            <div class="line-clamp-3 text-xs text-[#524B6B] mt-3 leading-5">
                                {{ item.description }}
                            </div>
                        </div>
                        <div class="text-[10px] flex items-center justify-between text-[#AAA6B9] mt-3">
                            <div>
                                {{ dayjs(item.create_time).format("YYYY/MM/DD") }}
                                创建
                            </div>
                            <div>绑定微信数：{{ item.wechat_count || 0 }}</div>
                        </div>
                        <div
                            class="absolute right-2 top-2 z-[1000] invisible group-hover:visible"
                            :class="[activeRobot == item.id ? '!visible' : '']">
                            <ElPopover
                                :show-arrow="false"
                                popper-class="!w-[130px] !min-w-[130px] !p-[6px] !rounded-xl"
                                @show="visibleChange(true, item.id)"
                                @hide="visibleChange(false, item.id)">
                                <template #reference>
                                    <div class="rotate-90 origin-center p-1">
                                        <Icon name="el-icon-MoreFilled"></Icon>
                                    </div>
                                </template>
                                <div class="flex flex-col gap-2">
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="handleEditRobot(item.id)">
                                        <Icon name="el-icon-Edit"></Icon>
                                        <span>编辑机器人</span>
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="handleDelete(item.id, index)">
                                        <Icon name="el-icon-Delete"></Icon>
                                        <span>删除</span>
                                    </div>
                                </div>
                            </ElPopover>
                        </div>
                    </div>
                </div>
                <div v-if="!pager.isLoad" class="text-center py-4 text-gray-500">暂无更多了</div>
            </template>
            <div v-else class="grow flex items-center justify-center bg-white rounded-xl">
                <ElEmpty description="暂无数据"></ElEmpty>
            </div>
        </div>
        <edit-pop v-if="showEdit" ref="editPopRef" @close="showEdit = false" @success="resetPage"> </edit-pop>
    </div>
    <RobotManage v-else @close="closeManage" />
</template>

<script setup lang="ts">
import { robotLists, deleteRobot } from "@/api/person_wechat";
import EditPop from "./edit.vue";
import RobotManage from "./manage.vue";
import dayjs from "dayjs";

const router = useRouter();

const nuxtApp = useNuxtApp();

const queryParams = reactive({
    page_no: 1,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: robotLists,
    params: queryParams,
    isScroll: true,
});

const activeRobot = ref<number | undefined>();
const visibleChange = (flag: boolean, id: number) => {
    if (!flag) {
        activeRobot.value = undefined;
    } else {
        activeRobot.value = id;
    }
};

const showEdit = ref(false);
const editPopRef = shallowRef<InstanceType<typeof EditPop>>();

const handleCreateRobot = async () => {
    showEdit.value = true;
    await nextTick();
    editPopRef.value.open();
};

const handleEditRobot = async (id: number) => {
    showEdit.value = true;
    await nextTick();
    editPopRef.value.open("edit");
    editPopRef.value.getDetail(id);
};

const handleDelete = (id: number, index: number) => {
    nuxtApp.$confirm({
        message: "确定删除吗？",
        onConfirm: async () => {
            try {
                await deleteRobot({ id });
                pager.lists.splice(index, 1);
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};

const showManage = ref(false);
const handleManage = (id: number) => {
    showManage.value = true;
    router.replace({
        query: {
            type: 1,
            id,
        },
    });
};

const closeManage = () => {
    showManage.value = false;
    router.replace({
        query: {
            type: 1,
        },
    });
};

const getQuery = () => {
    const { type, id } = router.currentRoute.value.query;
    if (id) {
        showManage.value = true;
    }
};

const load = async () => {
    queryParams.page_no += 1;
    await getLists();
};

await getLists();

getQuery();
</script>

<style scoped></style>
