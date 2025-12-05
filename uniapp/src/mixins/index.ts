import { App } from "vue";
import share from "./share";
export function setupMixin(app: App) {
	app.mixin(share);
}
