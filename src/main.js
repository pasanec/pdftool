/**
 * @copyright Copyright (c) 2023 Immanuel Pasanec <i@pasanec.de>
 *
 * @author Immanuel Pasanec <i@pasanec.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { registerFileAction } from "@nextcloud/files";
import { pdfAction } from "./pdfAction.ts";

const toFilesRuntimeAction = (action) => {
  const runtimeAction = {
    id: action.id,
    displayName: action.displayName,
    title: action.title,
    iconSvgInline: action.iconSvgInline,
    exec: action.exec,
  };

  for (const property of [
    "enabled",
    "execBatch",
    "order",
    "hotkey",
    "destructive",
    "parent",
    "default",
    "inline",
    "renderInline",
  ]) {
    if (action[property] !== undefined) {
      runtimeAction[property] = action[property];
    }
  }

  return runtimeAction;
};

const registerInFilesRuntime = async (action) => {
  try {
    const filesModule = await import(
      /* webpackIgnore: true */ "/dist/index-Dpj4ddZx.chunk.mjs"
    );
    if (typeof filesModule?.b === "function") {
      filesModule.b(toFilesRuntimeAction(action));
      return true;
    }
  } catch (error) {
    window.console.error("Could not register PDF action in Files runtime", error);
  }

  return false;
};

(async function () {
  window.console.info("Registering PDF Merge action");
  if (!(await registerInFilesRuntime(pdfAction))) {
    registerFileAction(pdfAction);
  }
})();
