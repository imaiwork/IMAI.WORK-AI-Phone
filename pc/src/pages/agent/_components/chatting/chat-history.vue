<template>
    <div class="h-full flex flex-col w-[300px] border-r-[1px] border-[#0000000d] flex-shrink-0">
        <!-- 头部返回按钮 -->
        <div class="h-[72px] flex-shrink-0 flex items-center border-b-[1px] border-[#0000000d] px-5">
            <div class="flex items-center gap-2 cursor-pointer" @click="router.back()">
                <Icon name="el-icon-ArrowLeft"></Icon>
                <div>返回</div>
            </div>
        </div>
        <!-- 聊天记录列表 -->
        <div class="grow min-h-0">
            <ElScrollbar v-if="pager.lists.length" @end-reached="emit('load-more')">
                <div>
                    <div
                        v-for="item in pager.lists"
                        :key="item.id"
                        class="h-[64px] px-5 border-b-[1px] border-[#0000000d] flex items-center gap-x-3 hover:bg-[#0000000d] cursor-pointer"
                        :class="{
                            'bg-[#0000000d]': isActive(item),
                        }"
                        @click="emit('select', item)">
                        <div class="flex-1">
                            <div class="line-clamp-1 break-all">
                                {{ item.title }}
                            </div>
                        </div>
                        <div class="w-[1px] h-3 bg-[#0000001a]"></div>
                        <div
                            class="flex-shrink-0 w-4 h-4 rounded-full flex items-center justify-center cursor-pointer bg-primary"
                            @click.stop="emit('delete', item)">
                            <Icon name="el-icon-Close" color="#ffffff" size="10"></Icon>
                        </div>
                    </div>
                    <div v-if="!pager.isLoad" class="text-tx-secondary text-center text-xs w-full py-4">
                        暂无更多了~
                    </div>
                </div>
            </ElScrollbar>
            <div v-else class="h-full flex items-center justify-center">
                <ElEmpty />
            </div>
        </div>
        <!-- 底部操作按钮 -->
        <div class="flex-shrink-0 p-3 border-t-[1px] border-[#0000000d]">
            <div>
                <ElButton
                    type="primary"
                    class="w-full !h-[50px] !rounded-xl"
                    :disabled="isReceiving"
                    @click="emit('create')">
                    创建新对话
                </ElButton>
            </div>
            <div class="mt-2">
                <ElButton class="w-full !h-[50px] !rounded-xl" :disabled="isReceiving" @click="emit('delete')"
                    >清除对话</ElButton
                >
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
// @ts-nocheck
/**
 * @description 聊天记录侧边栏组件
 * @summary 负责展示、选择、创建和删除聊天记录
 */
const props = defineProps<{
    pager: object; // 分页器对象
    currentRecordId: string | null; // 当前选中的记录ID
    isReceiving: boolean; // 是否正在接收消息
}>();

const emit = defineEmits<{
    (e: "select", item: any): void;
    (e: "create"): void;
    (e: "delete", id?: string): void;
    (e: "load-more"): void;
}>();

const router = useRouter();

const isActive = (item: any) => {
    if (item.task_id) {
        return props.currentRecordId == item.task_id;
    }
    if (item.conversation_id) {
        return props.currentRecordId == item.conversation_id;
    }
    return false;
};
</script>
