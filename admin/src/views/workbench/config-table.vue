<template>
    <el-card class="!border-none" shadow="never" v-if="data.length > 0">
        <template #header>
            <div>{{ title }}</div>
        </template>
        <el-table :data="data">
            <el-table-column prop="name" label="名称" min-width="150">
                <template #default="{ row }">
                    <el-tag>{{ row.name }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column label="成本价格" min-width="350">
                <template #default="{ row }">
                    <el-tag type="warning">{{ row.cast_unit }}</el-tag>
                    <div class="text-xs">
                        {{ row.description }}
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="用户价格" min-width="200">
                <template #default="{ row }">
                    <el-tag type="primary">{{ row.unit }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column prop="times" label="调用次数" width="150"></el-table-column>
        </el-table>
    </el-card>
</template>

<script setup lang="ts">
import { defineProps } from "vue";

const props = defineProps<{
    title: string;
    data: any[];
}>();
</script>

<style scoped></style>
