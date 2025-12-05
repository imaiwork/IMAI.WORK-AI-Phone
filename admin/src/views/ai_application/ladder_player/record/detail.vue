<template>
	<div>
		<el-card class="!border-none mb-4" shadow="never">
			<el-page-header content="分析结果" @back="$router.back()" />
		</el-card>
		<el-card class="!border-none" shadow="never">
			<el-tabs v-model="activeTab">
				<el-tab-pane label="分析" name="analysis">
					<Analysis :detail="detail" />
				</el-tab-pane>
				<el-tab-pane label="聊天记录" name="chatlog">
					<Chatlog :id="route.query.id" :scene-detail="sceneDetail" />
				</el-tab-pane>
			</el-tabs>
		</el-card>
	</div>
</template>

<script setup lang="ts">
import { getLpAnalysisDetail } from "@/api/ai_application/ladder_player/record";
import { lpSceneDetail } from "@/api/ai_application/ladder_player/scene";
import Analysis from "./analysis.vue";
import Chatlog from "./chatlog.vue";
const route = useRoute();
const activeTab = ref("analysis");
const detail = ref<any>({});
const sceneDetail = ref<any>({});

const getDetail = async () => {
	const data = await getLpAnalysisDetail({ id: route.query.id });
	detail.value = data || {};
	getSceneDetail();
};

const getSceneDetail = async () => {
	const data = await lpSceneDetail({ id: detail.value?.scene_id });
	sceneDetail.value = data || {};
};

onMounted(() => {
	getDetail();
});
</script>

<style scoped></style>
