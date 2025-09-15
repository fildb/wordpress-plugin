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
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Loader2, AlertCircle, CheckCircle } from "lucide-react";

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
  const [status, setStatus] = useState(null);
  const [isGenerating, setIsGenerating] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState(null);

  // Load initial data
  useEffect(() => {
    loadSettings();
    loadPostTypes();
    loadStatus();
  }, []);

  const loadSettings = async () => {
    try {
      const response = await fetch(`${window.fdbAdmin.apiUrl}llm/settings`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.fdbAdmin.nonce,
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
      const response = await fetch(`${window.fdbAdmin.apiUrl}llm/post-types`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.fdbAdmin.nonce,
        },
      });

      const data = await response.json();
      if (response.ok && data) {
        setPostTypes(data);
      }
    } catch (error) {
      console.error("Failed to load post types:", error);
    }
  };

  const loadStatus = async () => {
    try {
      const response = await fetch(`${window.fdbAdmin.apiUrl}llm/status`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.fdbAdmin.nonce,
        },
      });

      const data = await response.json();
      if (response.ok && data) {
        setStatus(data);
      }
    } catch (error) {
      console.error("Failed to load status:", error);
    }
  };

  const saveSettings = async () => {
    setIsSaving(true);
    try {
      const response = await fetch(`${window.fdbAdmin.apiUrl}llm/settings`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.fdbAdmin.nonce,
        },
        body: JSON.stringify(settings),
      });

      if (response.ok) {
        setMessage({ type: "success", text: "Settings saved successfully!" });
      } else {
        const errorData = await response.json();
        setMessage({
          type: "error",
          text: errorData.message || "Failed to save settings",
        });
      }
    } catch (error) {
      setMessage({ type: "error", text: "Failed to save settings" });
    } finally {
      setIsSaving(false);
      setTimeout(() => setMessage(null), 3000);
    }
  };

  const generateFile = async () => {
    setIsGenerating(true);
    try {
      const response = await fetch(`${window.fdbAdmin.apiUrl}llm/generate`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.fdbAdmin.nonce,
        },
      });

      if (response.ok) {
        setMessage({
          type: "success",
          text: "LLM file generated successfully!",
        });
        loadStatus(); // Refresh status
      } else {
        const errorData = await response.json();
        setMessage({
          type: "error",
          text: errorData.message || "Failed to generate file",
        });
      }
    } catch (error) {
      setMessage({ type: "error", text: "Failed to generate file" });
    } finally {
      setIsGenerating(false);
      setTimeout(() => setMessage(null), 3000);
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
      <div className="flex justify-end">
        <Button
          onClick={generateFile}
          disabled={isGenerating}
          className="bg-blue-600 hover:bg-blue-700">
          {isGenerating ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Generating...
            </>
          ) : (
            "Generate Now"
          )}
        </Button>
      </div>

      {message && (
        <Alert
          className={
            message.type === "error"
              ? "border-red-500 bg-red-50"
              : "border-green-500 bg-green-50"
          }>
          {message.type === "error" ? (
            <AlertCircle className="h-4 w-4 text-red-600" />
          ) : (
            <CheckCircle className="h-4 w-4 text-green-600" />
          )}
          <AlertDescription
            className={
              message.type === "error" ? "text-red-700" : "text-green-700"
            }>
            {message.text}
          </AlertDescription>
        </Alert>
      )}

      {/* Status Card */}
      {status && (
        <Card>
          <CardHeader>
            <CardTitle>File Status</CardTitle>
            <CardDescription>
              Current status of your llms.txt file
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div>
                <Label className="text-sm text-muted-foreground">Status</Label>
                <p
                  className={`font-medium ${
                    status.file_exists ? "text-green-600" : "text-red-600"
                  }`}>
                  {status.file_exists ? "Generated" : "Not Generated"}
                </p>
              </div>
              <div>
                <Label className="text-sm text-muted-foreground">
                  Last Generated
                </Label>
                <p className="font-medium">{status.last_generated_hr}</p>
              </div>
              <div>
                <Label className="text-sm text-muted-foreground">
                  File Size
                </Label>
                <p className="font-medium">{status.file_size_hr}</p>
              </div>
              <div>
                <Label className="text-sm text-muted-foreground">
                  File URL
                </Label>
                <a
                  href={status.file_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="font-medium text-blue-600 hover:text-blue-800">
                  View File
                </a>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

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
