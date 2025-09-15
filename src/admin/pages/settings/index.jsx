import { Separator } from "@/components/ui/separator";
import SettingsLayout from "@/admin/pages/settings/layout";
import LLMGenerator from "@/admin/pages/settings/llm-generator";

export default function Settings() {
  return (
    <SettingsLayout>
      <div className="space-y-6">
        <LLMGenerator />
      </div>
    </SettingsLayout>
  );
}
