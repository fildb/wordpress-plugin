import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Loader2 } from "lucide-react";

export default function LLMGenerator() {
  const [settings, setSettings] = useState({
    auto_update: true,
    post_types: ["post", "page"],
    include_excerpts: true,
    include_meta: false,
    include_taxonomies: false,
    max_posts_per_type: 50,
  });

  const [postTypes, setPostTypes] = useState([]);
  const [isSaving, setIsSaving] = useState(false);

  // Load initial data
  useEffect(() => {
    loadSettings();
    loadPostTypes();
  }, []);

  const loadSettings = async () => {
    try {
      const response = await fetch(`${window.fidabrAdmin.apiUrl}llm/settings`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.fidabrAdmin.nonce,
        },
      });

      const data = await response.json();
      if (response.ok && data) {
        setSettings(data);
      }
    } catch (error) {
      console.error("Failed to load settings:", error);
    }
  };

  const loadPostTypes = async () => {
    try {
      const response = await fetch(
        `${window.fidabrAdmin.apiUrl}llm/post-types`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": window.fidabrAdmin.nonce,
          },
        },
      );

      const data = await response.json();
      if (response.ok && data) {
        setPostTypes(data);
      }
    } catch (error) {
      console.error("Failed to load post types:", error);
    }
  };

  const saveSettings = async () => {
    setIsSaving(true);
    try {
      const response = await fetch(`${window.fidabrAdmin.apiUrl}llm/settings`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.fidabrAdmin.nonce,
        },
        body: JSON.stringify(settings),
      });

      if (!response.ok) {
        const errorData = await response.json();
        console.error("Failed to save settings:", errorData.message || "Unknown error");
      }
    } catch (error) {
      console.error("Failed to save settings:", error);
    } finally {
      setIsSaving(false);
    }
  };


  const handlePostTypeChange = (postType, checked) => {
    setSettings((prev) => ({
      ...prev,
      post_types: checked
        ? [...prev.post_types, postType]
        : prev.post_types.filter((type) => type !== postType),
    }));
  };

  return (
    <div className="space-y-6">

      {/* Settings Card */}
      <Card>
        <CardHeader>
          <CardTitle>Generation Settings</CardTitle>
          <CardDescription>
            Configure how your llms.txt file is generated
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Auto Update */}
          <div className="flex items-center space-x-2">
            <Switch
              id="auto-update"
              checked={settings.auto_update}
              onCheckedChange={(checked) =>
                setSettings((prev) => ({ ...prev, auto_update: checked }))
              }
            />
            <Label htmlFor="auto-update" className="text-sm font-medium">
              Automatic updates when content changes
            </Label>
          </div>

          {/* Post Types */}
          <div className="space-y-3">
            <Label className="text-sm font-medium">
              Content Types to Include
            </Label>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
              {postTypes.map((postType) => (
                <div
                  key={postType.name}
                  className="flex items-center space-x-2">
                  <Checkbox
                    id={postType.name}
                    checked={settings.post_types.includes(postType.name)}
                    onCheckedChange={(checked) =>
                      handlePostTypeChange(postType.name, !!checked)
                    }
                  />
                  <Label htmlFor={postType.name} className="text-sm">
                    {postType.label} ({postType.count})
                  </Label>
                </div>
              ))}
            </div>
          </div>

          {/* Content Options */}
          <div className="space-y-3">
            <Label className="text-sm font-medium">Content Options</Label>
            <div className="space-y-3">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="include-excerpts"
                  checked={settings.include_excerpts}
                  onCheckedChange={(checked) =>
                    setSettings((prev) => ({
                      ...prev,
                      include_excerpts: !!checked,
                    }))
                  }
                />
                <Label htmlFor="include-excerpts" className="text-sm">
                  Include post excerpts
                </Label>
              </div>

              <div className="flex items-center space-x-2">
                <Checkbox
                  id="include-meta"
                  checked={settings.include_meta}
                  onCheckedChange={(checked) =>
                    setSettings((prev) => ({
                      ...prev,
                      include_meta: !!checked,
                    }))
                  }
                />
                <Label htmlFor="include-meta" className="text-sm">
                  Include metadata (author, date, etc.)
                </Label>
              </div>

              <div className="flex items-center space-x-2">
                <Checkbox
                  id="include-taxonomies"
                  checked={settings.include_taxonomies}
                  onCheckedChange={(checked) =>
                    setSettings((prev) => ({
                      ...prev,
                      include_taxonomies: !!checked,
                    }))
                  }
                />
                <Label htmlFor="include-taxonomies" className="text-sm">
                  Include categories and tags
                </Label>
              </div>
            </div>
          </div>

          {/* Save Button */}
          <div className="pt-4">
            <Button
              onClick={saveSettings}
              disabled={isSaving}
              className="w-full md:w-auto">
              {isSaving ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Saving...
                </>
              ) : (
                "Save Settings"
              )}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
