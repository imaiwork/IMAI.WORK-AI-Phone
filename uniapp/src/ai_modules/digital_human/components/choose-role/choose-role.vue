<template>
    <popup-bottom v-model="show" title="请选择角色" custom-class="bg-[#F9FAFB]" :is-disabled-touch="true">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="flex items-center justify-between px-[32rpx]">
                    <view>
                        <u-tabs
                            :list="[
                                { name: '我的角色', value: 0 },
                                { name: '公开角色', value: 1 },
                            ]"
                            bg-color="transparent"
                            bold
                            :current="typeIndex"
                            @change="handleChange"></u-tabs>
                    </view>
                    <view class="text-primary font-bold" @click="toCreateRole">创建角色</view>
                </view>
                <view class="grow min-h-0 mt-4">
                    <z-paging
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="py-[30rpx] px-[32rpx] grid grid-cols-3 gap-2">
                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="h-[288rpx] rounded-[24rpx] relative overflow-hidden card-gradient"
                                @click.stop="chooseAnchor(item)">
                                <image :src="item.pic" class="w-full h-full" mode="aspectFill"></image>
                                <view class="absolute bottom-1 left-0 right-0 mx-2 flex items-center justify-center">
                                    <view
                                        class="w-fit bg-[#000000b3] py-[4rpx] text-xs rounded-[20rpx] text-white px-2 line-clamp-1 break-all">
                                        {{ item.name }}
                                    </view>
                                </view>
                                <view
                                    v-if="item.status == 0"
                                    class="z-[222] absolute top-0 left-0 w-full h-full flex items-center justify-center bg-[#00000080]">
                                    <view class="bg-primary text-xs font-bold text-white rounded-[10rpx] px-2 py-1"
                                        >克隆中</view
                                    >
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getSoraRoleList, getSoraRolePublicList } from "@/api/digital_human";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    activeIds: {
        type: Array,
        default: [],
    },
});
const emit = defineEmits(["update:modelValue", "confirm", "close"]);

const show = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});

const typeIndex = ref(0);

const pagingRef = shallowRef();
const dataLists = ref<any[]>([]);

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await (typeIndex.value == 0 ? getSoraRoleList : getSoraRolePublicList)({
            page_no,
            page_size,
            status: [0, 1],
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleChange = (index: number) => {
    typeIndex.value = index;
    pagingRef.value?.reload();
};

const chooseAnchor = (item: any) => {
    if (item.status != 1) {
        uni.$u.toast("只能选择已生成角色的视频");
        return;
    }
    close();
    emit("confirm", item);
};

const toCreateRole = () => {
    show.value = false;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/sora_create_role/sora_create_role",
    });
};

const close = () => {
    show.value = false;
    emit("close");
};
</script>

<style scoped></style>
