import { useState, useEffect, useRef } from "react";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Progress } from "@/components/ui/progress";
import {
  Loader2,
  AlertCircle,
  CheckCircle,
  FileText,
  Database,
  HardDrive,
  BarChart3
} from "lucide-react";

export default function DashboardPage() {
  const [status, setStatus] = useState(null);
  const [statistics, setStatistics] = useState(null);
  const [isGenerating, setIsGenerating] = useState(false);
  const [isClearing, setIsClearing] = useState(false);
  const [isLoadingStatistics, setIsLoadingStatistics] = useState(false);
  const [message, setMessage] = useState(null);
  const [progress, setProgress] = useState(null);
  const canceledRef = useRef(false);

  // Load initial data
  useEffect(() => {
    loadStatus();
    loadStatistics();
  }, []);

  const loadStatus = async () => {
    try {
      const response = await fetch(`${window.fidabrAdmin.apiUrl}llm/status`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.fidabrAdmin.nonce,
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

  const loadStatistics = async (showLoading = false) => {
    if (showLoading) {
      setIsLoadingStatistics(true);
    }

    try {
      const response = await fetch(`${window.fidabrAdmin.apiUrl}llm/statistics`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.fidabrAdmin.nonce,
        },
      });

      const data = await response.json();
      if (response.ok && data) {
        setStatistics(data);
      }
    } catch (error) {
      console.error("Failed to load statistics:", error);
      // Fallback statistics
      setStatistics({
        posts_in_llm: 0,
        total_posts: 0,
        pages_in_llm: 0,
        total_pages: 0,
        cdn_storage_used: 0,
        cdn_storage_total: 1073741824 // 1GB in bytes
      });
    } finally {
      if (showLoading) {
        setIsLoadingStatistics(false);
      }
    }
  };

  const processGeneration = async (isStart = false) => {
    // Check if canceled
    if (canceledRef.current) {
      setIsGenerating(false);
      setMessage({
        type: "warning",
        text: "Generation was canceled",
      });
      setTimeout(() => {
        setProgress(null);
      }, 3000);
      return;
    }

    try {
      const response = await fetch(`${window.fidabrAdmin.apiUrl}llm/generate`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.fidabrAdmin.nonce,
        },
        body: isStart ? JSON.stringify({ start: "1" }) : undefined,
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || "Failed to process generation");
      }

      const data = await response.json();
      setProgress(data);

      if (data.finished) {
        // Generation completed
        setIsGenerating(false);
        setMessage({
          type: "success",
          text: "LLM file generated successfully!",
        });
        loadStatus();
        loadStatistics();
        setTimeout(() => {
          setProgress(null);
        }, 3000);
      } else {
        // Update statistics after each item is processed
        loadStatistics();

        // Continue with next item after a short delay
        setTimeout(() => {
          processGeneration(false).catch((error) => {
            console.error("Error in recursive processing:", error);
            setIsGenerating(false);
            setMessage({
              type: "error",
              text: "Processing failed: " + error.message,
            });
            setTimeout(() => {
              setProgress(null);
            }, 3000);
          });
        }, 200);
      }
    } catch (error) {
      console.error("Error during processing:", error);
      setIsGenerating(false);
      setMessage({
        type: "error",
        text: error.message || "An unexpected error occurred",
      });
      setTimeout(() => {
        setProgress(null);
      }, 3000);
    }
  };

  const generateFile = async () => {
    setIsGenerating(true);
    canceledRef.current = false;
    setMessage(null);

    // Show progress bar instantly with initial state
    setProgress({
      finished: false,
      items: { parsed: 0, total: 0 },
      last: null,
    });

    // Start the recursive processing
    processGeneration(true);
  };

  const cancelGeneration = () => {
    canceledRef.current = true;
    setIsGenerating(false);
  };

  const clearMetadata = async () => {
    if (
      !confirm(
        "Are you sure you want to reset all plugin data? This will clear all metadata from posts/pages, remove the LLMs.txt file, and reset CDN storage usage. This action cannot be undone.",
      )
    ) {
      return;
    }

    setIsClearing(true);
    setMessage(null);

    try {
      const response = await fetch(
        `${window.fidabrAdmin.apiUrl}llm/clear-metadata`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": window.fidabrAdmin.nonce,
          },
        },
      );

      const data = await response.json();

      if (response.ok) {
        setMessage({
          type: "success",
          text: data.message || `Successfully reset plugin data.`,
        });
        loadStatus();
        loadStatistics();
      } else {
        setMessage({
          type: "error",
          text: data.message || "Failed to reset plugin data",
        });
      }
    } catch (error) {
      setMessage({
        type: "error",
        text: "Failed to reset plugin data: " + error.message,
      });
    } finally {
      setIsClearing(false);
      setTimeout(() => setMessage(null), 5000);
    }
  };

  const formatBytes = (bytes) => {
    if (bytes === 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
  };

  const getStoragePercentage = () => {
    if (!statistics) return 0;
    return Math.min((statistics.cdn_storage_used / statistics.cdn_storage_total) * 100, 100);
  };

  return (
    <div className="space-y-6 p-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
          <p className="text-muted-foreground">
            Overview of your LLM.txt file generation and content statistics
          </p>
        </div>
        <div className="flex gap-2">
          {isGenerating && (
            <Button
              onClick={cancelGeneration}
              variant="outline"
              className="border-red-300 text-red-600 hover:bg-red-50">
              Cancel
            </Button>
          )}
          <Button
            onClick={clearMetadata}
            disabled={isGenerating || isClearing}
            variant="outline"
            className="border-orange-300 text-orange-600 hover:bg-orange-50">
            {isClearing ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Resetting...
              </>
            ) : (
              "Reset"
            )}
          </Button>
          <Button
            onClick={generateFile}
            disabled={isGenerating || isClearing}
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
      </div>

      {progress && (
        <Card className="border-blue-200 bg-blue-50">
          <CardHeader className="pb-3">
            <CardTitle className="text-blue-800">Generation Progress</CardTitle>
            <CardDescription className="text-blue-600">
              Processing items...
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span>Progress</span>
                <span>
                  {progress.items.parsed} / {progress.items.total}
                </span>
              </div>
              <Progress
                value={
                  progress.items.total > 0
                    ? (progress.items.parsed / progress.items.total) * 100
                    : 0
                }
                className="h-2"
              />
            </div>

            {progress.last && (
              <div className="text-sm">
                <p className="text-xs text-muted-foreground mb-1">
                  Last Processed
                </p>
                <p className="font-medium flex items-center gap-1">
                  <FileText className="h-3 w-3" />
                  {progress.last.title} ({progress.last.type})
                </p>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {message && (
        <Alert
          className={
            message.type === "error"
              ? "border-red-500 bg-red-50"
              : message.type === "warning"
              ? "border-yellow-500 bg-yellow-50"
              : "border-green-500 bg-green-50"
          }>
          {message.type === "error" ? (
            <AlertCircle className="h-4 w-4 text-red-600" />
          ) : (
            <CheckCircle className="h-4 w-4 text-green-600" />
          )}
          <AlertDescription
            className={
              message.type === "error"
                ? "text-red-700"
                : message.type === "warning"
                ? "text-yellow-700"
                : "text-green-700"
            }>
            {message.text}
          </AlertDescription>
        </Alert>
      )}

      {/* Statistics Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {/* LLM File Status Card */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">LLM File Status</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {status?.file_exists ? (
                <span className="text-green-600">Generated</span>
              ) : (
                <span className="text-red-600">Not Generated</span>
              )}
            </div>
            <p className="text-xs text-muted-foreground">
              {status?.last_generated_hr || "Never generated"}
            </p>
            <div className="mt-2">
              <p className="text-sm">
                Size: <span className="font-medium">{status?.file_size_hr || "0 B"}</span>
              </p>
              {status?.file_url && (
                <a
                  href={status.file_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-xs text-blue-600 hover:text-blue-800">
                  View File →
                </a>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Content Statistics Card */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Content Statistics</CardTitle>
            {isLoadingStatistics ? (
              <Loader2 className="h-4 w-4 text-muted-foreground animate-spin" />
            ) : (
              <Database className="h-4 w-4 text-muted-foreground" />
            )}
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <div>
                <div className="text-lg font-bold">
                  {statistics?.posts_in_llm || 0} / {statistics?.total_posts || 0}
                </div>
                <p className="text-xs text-muted-foreground">Posts included</p>
              </div>
              <div>
                <div className="text-lg font-bold">
                  {statistics?.pages_in_llm || 0} / {statistics?.total_pages || 0}
                </div>
                <p className="text-xs text-muted-foreground">Pages included</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* CDN Storage Card */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">CDN Storage</CardTitle>
            {isLoadingStatistics ? (
              <Loader2 className="h-4 w-4 text-muted-foreground animate-spin" />
            ) : (
              <HardDrive className="h-4 w-4 text-muted-foreground" />
            )}
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {formatBytes(statistics?.cdn_storage_used || 0)}
            </div>
            <p className="text-xs text-muted-foreground">
              of {formatBytes(statistics?.cdn_storage_total || 1073741824)} used
            </p>
            <div className="mt-2">
              <Progress
                value={getStoragePercentage()}
                className="h-2"
              />
              <p className="text-xs text-muted-foreground mt-1">
                {getStoragePercentage().toFixed(1)}% used
              </p>
            </div>
          </CardContent>
        </Card>
      </div>

    </div>
  );
}