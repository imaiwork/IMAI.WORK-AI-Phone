import { ElLoading } from "element-plus";
import "element-plus/dist/index.css";
export default defineNuxtPlugin((nuxtApp) => {
	const plugins = [ElLoading];
	for (const plugin of plugins) {
		nuxtApp.vueApp.use(plugin);
	}
});
