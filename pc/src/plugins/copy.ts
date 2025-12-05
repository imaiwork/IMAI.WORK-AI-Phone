import feedback from "@/utils/feedback";
import useClipboard from "vue-clipboard3";
const clipboard = "data-clipboard-text";
export default defineNuxtPlugin((nuxtApp) => {
	nuxtApp.vueApp.directive("copy", {
		mounted(
			el,
			{
				arg, // role | permission
				value, // string | array
			}
		) {
			el.setAttribute(clipboard, value);
			const { toClipboard } = useClipboard();

			el.onclick = () => {
				toClipboard(el.getAttribute(clipboard)!)
					.then(() => {
						feedback.msgSuccess("复制成功");
					})
					.catch(() => {
						feedback.msgError("复制失败");
					});
			};
		},
		updated(
			el,
			{
				arg, // role | permission
				value, // string | array
			}
		) {
			el.setAttribute(clipboard, value);
		},
	});
});
// 移除元素
const removeEl = (el) => el.parentNode && el.parentNode.removeChild(el);
