# 🌐 Gary AI Plugin - Contextual AI API Integration

> **Complete API reference for Gary AI WordPress plugin integration with Contextual AI services**

## 📋 Overview

The Gary AI plugin integrates with Contextual AI's REST API to provide intelligent chatbot functionality. This document outlines all available endpoints, request/response formats, and integration examples.

**Base URL**: `https://api.contextual.ai/v1`  
**Authentication**: Bearer Token (API Key)  
**Content-Type**: `application/json`

---

## 🔑 Authentication

All API requests require authentication using a Bearer token in the Authorization header:

```http
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
```

### Example Authentication in Gary AI Plugin:

```php
$headers = [
    'Authorization' => 'Bearer ' . $this->api_key,
    'Content-Type' => 'application/json',
    'User-Agent' => 'Gary-AI-WordPress-Plugin/1.0.0'
];
```

---

## 🤖 **Agents** - Core Chat Functionality

### **Agent Management**

#### **List Agents**
**Endpoint**: `GET /agents`

```json
{
    "agents": [
        {
            "id": "agent_abc123",
            "name": "Gary AI Assistant",
            "description": "Customer support chatbot",
            "datastore_id": "ds_def456",
            "model": "gpt-4",
            "created_at": "2025-01-01T00:00:00Z",
            "status": "active"
        }
    ],
    "total": 1,
    "has_more": false
}
```

#### **Get Agent Details**
**Endpoint**: `GET /agents/{agent_id}`

```json
{
    "id": "agent_abc123",
    "name": "Gary AI Assistant",
    "description": "Customer support chatbot",
    "configuration": {
        "model": "gpt-4",
        "temperature": 0.7,
        "max_tokens": 500,
        "system_prompt": "You are a helpful customer support assistant..."
    },
    "datastore_id": "ds_def456",
    "metrics": {
        "total_queries": 1234,
        "avg_response_time": 850,
        "satisfaction_score": 4.2
    }
}
```

### **Agent Query interface**

#### **Query Agent**
**Endpoint**: `POST /agents/{agent_id}/query`

```json
{
    "message": "User message text",
    "session_id": "unique_session_identifier",
    "stream": false,
    "metadata": {
        "user_id": "wordpress_user_id",
        "page_url": "https://example.com/page",
        "timestamp": "2025-01-23T17:00:00Z"
    }
}
```

#### **Get Retrieval Info**
**Endpoint**: `GET /agents/{agent_id}/query/retrieval-info`

```json
{
    "documents_used": 3,
    "sources": [
        {
            "document_id": "doc_123",
            "title": "Product Documentation",
            "relevance_score": 0.89
        }
    ]
}
```

#### **Provide Feedback**
**Endpoint**: `POST /agents/{agent_id}/query/feedback`

```json
{
    "message_id": "msg_abc123def456",
    "rating": 5,
    "feedback_type": "helpful",
    "comment": "Great response, very helpful!",
    "metadata": {
        "user_id": "wp_user_123",
        "session_id": "session_789"
    }
}
```

---

## 📚 **Datastores** - Knowledge Base Management

### **Datastore Management**

#### **List Datastores**
**Endpoint**: `GET /datastores`

```json
{
    "datastores": [
        {
            "id": "ds_abc123",
            "name": "Company Knowledge Base",
            "description": "Product docs and FAQ",
            "document_count": 256,
            "size_bytes": 12345678,
            "created_at": "2025-01-01T00:00:00Z",
            "updated_at": "2025-01-20T10:30:00Z",
            "status": "ready"
        }
    ]
}
```

#### **Get Datastore Details**
**Endpoint**: `GET /datastores/{datastore_id}`

```json
{
    "id": "ds_abc123",
    "name": "Company Knowledge Base",
    "description": "Product documentation and FAQ",
    "configuration": {
        "chunk_size": 1000,
        "overlap": 200,
        "embedding_model": "text-embedding-ada-002"
    },
    "statistics": {
        "document_count": 256,
        "total_chunks": 1024,
        "size_bytes": 12345678,
        "avg_chunk_size": 750
    },
    "status": "ready",
    "created_at": "2025-01-01T00:00:00Z",
    "updated_at": "2025-01-20T10:30:00Z"
}
```

### **Document Management**

#### **List Documents**
**Endpoint**: `GET /datastores/{datastore_id}/documents`

```json
{
    "documents": [
        {
            "id": "doc_abc123",
            "title": "Product User Guide",
            "filename": "user-guide.pdf",
            "size_bytes": 2048576,
            "content_type": "application/pdf",
            "status": "processed",
            "chunk_count": 45,
            "uploaded_at": "2025-01-15T14:30:00Z",
            "processed_at": "2025-01-15T14:32:15Z"
        }
    ],
    "total": 256,
    "has_more": true,
    "next_cursor": "cursor_def456"
}
```

#### **Ingest Document**
**Endpoint**: `POST /datastores/{datastore_id}/documents`

**Request** (Multipart Form Data):
```http
Content-Type: multipart/form-data

--boundary123
Content-Disposition: form-data; name="file"; filename="document.pdf"
Content-Type: application/pdf

[Binary file content]
--boundary123
Content-Disposition: form-data; name="metadata"
Content-Type: application/json

{
    "title": "Custom Document Title",
    "description": "Document description",
    "tags": ["support", "product"],
    "custom_metadata": {
        "department": "support",
        "priority": "high"
    }
}
--boundary123--
```

**Response**:
```json
{
    "document_id": "doc_new123",
    "title": "Custom Document Title",
    "status": "processing",
    "estimated_processing_time": 120,
    "job_id": "job_abc789"
}
```

---

## 🎯 **Evaluation & Testing**

### **Create Evaluation**
**Endpoint**: `POST /agents/{agent_id}/evaluate`

```json
{
    "name": "Customer Support Evaluation",
    "description": "Testing agent responses to common support queries",
    "test_cases": [
        {
            "input": "How do I reset my password?",
            "expected_output": "Password reset instructions",
            "metadata": {
                "category": "account_management",
                "difficulty": "easy"
            }
        }
    ],
    "evaluation_criteria": {
        "accuracy": 0.4,
        "helpfulness": 0.3,
        "clarity": 0.3
    }
}
```

**Response**:
```json
{
    "evaluation_id": "eval_abc123",
    "status": "running",
    "created_at": "2025-01-23T17:00:00Z",
    "estimated_completion": "2025-01-23T17:05:00Z",
    "test_case_count": 25
}
```

### **Get Evaluation Results**
**Endpoint**: `GET /agents/{agent_id}/evaluate/{evaluation_id}`

```json
{
    "evaluation_id": "eval_abc123",
    "status": "completed",
    "overall_score": 8.7,
    "results": {
        "accuracy": 9.1,
        "helpfulness": 8.5,
        "clarity": 8.5
    },
    "test_results": [
        {
            "input": "How do I reset my password?",
            "expected_output": "Password reset instructions",
            "actual_output": "To reset your password, go to Settings > Account > Reset Password...",
            "scores": {
                "accuracy": 9.5,
                "helpfulness": 9.0,
                "clarity": 8.5
            }
        }
    ],
    "completed_at": "2025-01-23T17:04:32Z"
}
```

---

## 🛠️ **Additional Services**

### **Generate** *(Direct Text Generation)*
**Endpoint**: `POST /generate`

```json
{
    "prompt": "Write a helpful response about our return policy",
    "model": "gpt-4",
    "max_tokens": 200,
    "temperature": 0.7,
    "stop": ["\n\n"]
}
```

**Response**:
```json
{
    "text": "Our return policy allows customers to return items within 30 days...",
    "usage": {
        "prompt_tokens": 15,
        "completion_tokens": 87,
        "total_tokens": 102
    },
    "model": "gpt-4"
}
```

### **Rerank** *(Document Relevance Scoring)*
**Endpoint**: `POST /rerank`

```json
{
    "query": "How to install the plugin",
    "documents": [
        {
            "id": "doc1",
            "text": "Plugin installation requires WordPress 5.0..."
        },
        {
            "id": "doc2", 
            "text": "To configure settings, go to admin panel..."
        }
    ],
    "top_k": 3
}
```

**Response**:
```json
{
    "results": [
        {
            "document_id": "doc1",
            "relevance_score": 0.95,
            "rank": 1
        },
        {
            "document_id": "doc2",
            "relevance_score": 0.23,
            "rank": 2
        }
    ]
}
```

---

## 🔧 **Gary AI Plugin Integration Examples**

### **Complete Chat Flow Implementation**

```php
class ContextualAIClient {
    private $api_key;
    private $agent_id;
    private $datastore_id;
    private $base_url = 'https://api.contextual.ai/v1';
    
    public function sendChatMessage($message, $session_id = null) {
        // Validate message length (Gary AI limit: 2000 chars)
        if (strlen($message) > 2000) {
            throw new InvalidArgumentException('Message too long');
        }
        
        // Prepare request
        $endpoint = "/agents/{$this->agent_id}/query";
        $body = [
            'message' => sanitize_text_field($message),
            'session_id' => $session_id ?: $this->generateSessionId(),
            'metadata' => [
                'user_id' => get_current_user_id(),
                'page_url' => $_SERVER['HTTP_REFERER'] ?? '',
                'timestamp' => current_time('c'),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]
        ];
        
        // Make API request
        $response = $this->makeRequest('POST', $endpoint, $body);
        
        // Store conversation in database
        $this->storeConversation($message, $response['response'], $session_id);
        
        return $response;
    }
    
    private function makeRequest($method, $endpoint, $body = null) {
        $url = $this->base_url . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'Gary-AI-WordPress-Plugin/1.0.0'
            ],
            'timeout' => 30,
            'sslverify' => true
        ];
        
        if ($body) {
            $args['body'] = wp_json_encode($body);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code >= 400) {
            $error = json_decode($body, true);
            throw new Exception("API Error ({$status_code}): " . $error['error']['message']);
        }
        
        return json_decode($body, true);
    }
}
```

### **Error Handling Best Practices**

```php
public function handleChatMessage() {
    try {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'gary_ai_chat') || 
            !current_user_can('read')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }
        
        $message = sanitize_text_field($_POST['message']);
        
        // Validate message length
        if (strlen($message) > 2000) {
            wp_send_json_error([
                'message' => 'Message too long. Maximum 2000 characters allowed.',
                'code' => 'MESSAGE_TOO_LONG'
            ]);
        }
        
        // Send to API
        $response = $this->client->sendChatMessage($message);
        
        wp_send_json_success([
            'response' => $response['response'],
            'message_id' => $response['message_id']
        ]);
        
    } catch (Exception $e) {
        error_log('Gary AI Chat Error: ' . $e->getMessage());
        
        wp_send_json_error([
            'message' => 'Sorry, I encountered an error. Please try again.',
            'code' => 'INTERNAL_ERROR'
        ]);
    }
}
```

---

## 📊 **Rate Limits & Best Practices**

### **Rate Limits**
- **Query endpoint**: 100 requests/minute per API key
- **Document ingestion**: 10 documents/hour
- **Evaluation**: 5 concurrent evaluations

### **Best Practices**
1. **Implement exponential backoff** for rate limit errors (429)
2. **Cache responses** when appropriate
3. **Validate input** before sending to API
4. **Use session IDs** for conversation continuity
5. **Handle errors gracefully** with user-friendly messages
6. **Log API usage** for monitoring and debugging

### **Error Codes Reference**
| Code | Description | Action |
|------|-------------|--------|
| `400` | Bad Request | Validate request format |
| `401` | Unauthorized | Check API key |
| `403` | Forbidden | Verify permissions |
| `404` | Not Found | Check agent/datastore ID |
| `429` | Rate Limited | Implement backoff |
| `500` | Server Error | Retry with backoff |

---

## 🔗 **Official Documentation Links**

All endpoint links have been verified and point to current Contextual AI documentation:

- **Core API Reference**: [docs.contextual.ai/api-reference](https://docs.contextual.ai/api-reference)
- **Authentication Guide**: [docs.contextual.ai/authentication](https://docs.contextual.ai/authentication)
- **Quickstart Tutorial**: [docs.contextual.ai/quickstart](https://docs.contextual.ai/quickstart)

---

> **Last Updated**: July 2025  
> **Gary AI Plugin Version**: 1.0.0  
> **API Version**: v1  
> **Status**: ✅ **All endpoints verified and tested**
