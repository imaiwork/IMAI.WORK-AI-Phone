<template>
    <div>
        <!-- 权限类型 -->
        <div class="flex flex-col gap-3">
            <div
                class="flex items-center gap-3 px-4 py-3 rounded-lg border cursor-pointer transition-all duration-200 select-none"
                :class="
                    permissions === 0
                        ? 'border-[#0065fb] bg-[#0065fb]/5'
                        : 'border-[#e4e7ed] bg-[#fafafa] hover:border-[#0065fb]/50'
                "
                @click="permissions = 0">
                <div>
                    <div class="text-[13px] font-medium text-[#303133]">全部人可用</div>
                    <div class="text-[12px] text-[#909399] mt-0.5">所有用户均可使用该智能体</div>
                </div>
            </div>

            <div
                class="flex items-center gap-3 px-4 py-3 rounded-lg border cursor-pointer transition-all duration-200 select-none"
                :class="
                    permissions === 1
                        ? 'border-primary bg-[#0065fb]/5'
                        : 'border-[#e4e7ed] bg-[#fafafa] hover:border-[#0065fb]/50'
                "
                @click="permissions = 1">
                <div>
                    <div class="text-[13px] font-medium text-[#303133]">仅会员可用</div>
                    <div class="text-[12px] text-[#909399] mt-0.5">仅指定会员等级的用户可使用该智能体</div>
                </div>
            </div>
        </div>

        <transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2">
            <div v-if="permissions === 1" class="rounded-lg border border-[#dbeafe] bg-[#f0f7ff] p-5 mt-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-1 text-[13px] text-[#606266]">
                        <span>请选择可使用的会员等级</span>
                        <span class="text-[#f56c6c]">*</span>
                        <span class="text-[#c0c4cc] ml-1">（可多选）</span>
                    </div>
                    <span
                        v-if="selectedLevelIds.length > 0"
                        class="text-[12px] text-primary cursor-pointer hover:opacity-70 transition-opacity"
                        @click="selectedLevelIds = []">
                        清空选择
                    </span>
                </div>

                <el-checkbox-group v-model="selectedLevelIds" class="flex flex-wrap gap-2 mb-4">
                    <el-checkbox v-for="level in memberLevelList" :key="level.id" :label="level.id" class="!m-0">
                        <div
                            class="flex items-center gap-1 px-3 py-1.5 rounded-md border text-[12px] transition-all duration-150"
                            :class="
                                selectedLevelIds.includes(level.id)
                                    ? 'border-primary bg-[#0065fb]/10 text-primary'
                                    : 'border-[#dbeafe] bg-white text-[#606266] hover:border-[#0065fb]/60'
                            ">
                            <el-icon v-if="selectedLevelIds.includes(level.id)" size="12"><Check /></el-icon>
                            {{ level.level_name }}
                        </div>
                    </el-checkbox>
                </el-checkbox-group>

                <div v-if="memberLevelList.length === 0" class="text-center text-[13px] text-[#c0c4cc] py-4">
                    暂无会员等级，请先前往添加
                </div>

                <div class="flex items-center gap-1.5 pt-3 border-t border-[#dbeafe]">
                    <el-icon size="12" class="text-[#f56c6c] shrink-0"><Warning /></el-icon>
                    <span class="text-[12px] text-[#f56c6c]">
                        会员等级来自
                        <span
                            class="text-primary underline underline-offset-2 cursor-pointer hover:opacity-70 transition-opacity"
                            @click="handleOpenUserLevel">
                            会员等级管理
                        </span>
                        页面，可前往新增或编辑
                    </span>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup lang="ts">
import { getRoutePath } from "@/router";
import { getUserLevelList } from "@/api/consumer";

const props = defineProps<{
    permissions?: number;
    memberLevelIds?: number[];
}>();

const emit = defineEmits<{
    (e: "update:permissions", val: number): void;
    (e: "update:memberLevelIds", val: number[]): void;
}>();

interface MemberLevel {
    id: number;
    level_name: string;
}

const router = useRouter();

const permissions = computed({
    get: () => Number(props.permissions ?? 0),
    set: (val: number) => emit("update:permissions", val),
});

const selectedLevelIds = computed({
    get: () => props.memberLevelIds ?? [],
    set: (val: number[]) => emit("update:memberLevelIds", val),
});

const memberLevelList = ref<MemberLevel[]>([]);

const handleOpenUserLevel = () => {
    router.push({ path: getRoutePath("user.user_level/lists") });
};

const fetchMemberLevelList = async () => {
    try {
        const { lists } = await getUserLevelList({ page_size: 2000 });
        memberLevelList.value = lists ?? [];
    } catch (error) {
        console.error("获取会员等级列表失败:", error);
    }
};

// permissions 切换为 0 时清空已选
watch(permissions, (val) => {
    if (val === 0 && selectedLevelIds.value.length > 0) {
        selectedLevelIds.value = [];
    }
});

onMounted(() => {
    fetchMemberLevelList();
});
</script>

<style lang="scss" scoped></style>
