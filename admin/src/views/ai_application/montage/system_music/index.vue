<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-tabs v-model="activeTab">
                <el-tab-pane label="模板中心曲库" name="center" lazy>
                    <center-list />
                </el-tab-pane>
                <el-tab-pane label="本站上传" name="local" lazy>
                    <local-list />
                </el-tab-pane>
            </el-tabs>
        </el-card>
    </div>
</template>

<script lang="ts" setup>
import CenterList from "./components/center-list.vue";
import LocalList from "./components/local-list.vue";

// 两个来源同一张表、同一套显隐与排序，分开 tab 是为了让「只读的同步内容」和
// 「可改名/删除的本站内容」操作边界一眼可见
const activeTab = ref("center");
</script>
