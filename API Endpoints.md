API Endpoints .


### **/datastores**

| Verb       | Path                               |                           |
| ---------- | ---------------------------------- | ------------------------- |
| **GET**    | `/datastores`                      |                           |
| **POST**   | `/datastores`                      |                           |
| **PUT**    | `/datastores/{datastore_id}/reset` |                           |
| **PUT**    | `/datastores/{datastore_id}`       |                           |
| **DELETE** | `/datastores/{datastore_id}`       |                           |
| **GET**    | `/datastores/{datastore_id}`       | ([docs.contextual.ai][1]) |

#### **/datastores/{id}/documents**

| Verb       | Path                                                          |                                                                                                                               |
| ---------- | ------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| **GET**    | `/datastores/{datastore_id}/documents`                        |                                                                                                                               |
| **POST**   | `/datastores/{datastore_id}/documents`                        |                                                                                                                               |
| **GET**    | `/datastores/{datastore_id}/documents/{document_id}/metadata` |                                                                                                                               |
| **POST**   | `/datastores/{datastore_id}/documents/{document_id}/metadata` |                                                                                                                               |
| **DELETE** | `/datastores/{datastore_id}/documents/{document_id}`          | ([docs.contextual.ai][2], [docs.contextual.ai][3], [docs.contextual.ai][4], [docs.contextual.ai][5], [docs.contextual.ai][6]) |

---

### **/agents**

| Verb       | Path                       |                                                    |
| ---------- | -------------------------- | -------------------------------------------------- |
| **GET**    | `/agents`                  |                                                    |
| **POST**   | `/agents`                  |                                                    |
| **PUT**    | `/agents/{agent_id}`       |                                                    |
| **DELETE** | `/agents/{agent_id}`       |                                                    |
| **GET**    | `/agents/{agent_id}`       |                                                    |
| **PUT**    | `/agents/{agent_id}/reset` | ([docs.contextual.ai][7], [docs.contextual.ai][8]) |

#### **/agents/{id}/query**

| Verb     | Path                                                   |                                                                                                         |
| -------- | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------- |
| **POST** | `/agents/{agent_id}/query`                             |                                                                                                         |
| **GET**  | `/agents/{agent_id}/query/{message_id}/retrieval/info` |                                                                                                         |
| **POST** | `/agents/{agent_id}/feedback`                          |                                                                                                         |
| **GET**  | `/agents/{agent_id}/metrics`                           | ([docs.contextual.ai][9], [docs.contextual.ai][10], [docs.contextual.ai][11], [docs.contextual.ai][12]) |

#### **/agents/{id}/evaluate**

| Verb     | Path                                                                        |                            |
| -------- | --------------------------------------------------------------------------- | -------------------------- |
| **POST** | `/agents/{agent_id}/evaluate`  *(Create Evaluation)*                        |                            |
| **GET**  | `/agents/{agent_id}/evaluate`  *(List Evaluations)*                         |                            |
| **GET**  | `/agents/{agent_id}/evaluate/{evaluation_id}`  *(Get Evaluation Metadata)*  |                            |
| **POST** | `/agents/{agent_id}/evaluate/{evaluation_id}/cancel`  *(Cancel Evaluation)* | ([docs.contextual.ai][13]) |

#### **/agents/{id}/datasets/evaluate**

| Verb       | Path                                                         |                            |
| ---------- | ------------------------------------------------------------ | -------------------------- |
| **GET**    | `/agents/{agent_id}/datasets/evaluate`                       |                            |
| **POST**   | `/agents/{agent_id}/datasets/evaluate`                       |                            |
| **GET**    | `/agents/{agent_id}/datasets/evaluate/{dataset_id}`          |                            |
| **PUT**    | `/agents/{agent_id}/datasets/evaluate/{dataset_id}`          |                            |
| **DELETE** | `/agents/{agent_id}/datasets/evaluate/{dataset_id}`          |                            |
| **GET**    | `/agents/{agent_id}/datasets/evaluate/{dataset_id}/metadata` | ([docs.contextual.ai][13]) |

#### **/agents/{id}/datasets/tune**

| Verb       | Path                                                     |                            |
| ---------- | -------------------------------------------------------- | -------------------------- |
| **GET**    | `/agents/{agent_id}/datasets/tune`                       |                            |
| **POST**   | `/agents/{agent_id}/datasets/tune`                       |                            |
| **GET**    | `/agents/{agent_id}/datasets/tune/{dataset_id}`          |                            |
| **PUT**    | `/agents/{agent_id}/datasets/tune/{dataset_id}`          |                            |
| **DELETE** | `/agents/{agent_id}/datasets/tune/{dataset_id}`          |                            |
| **GET**    | `/agents/{agent_id}/datasets/tune/{dataset_id}/metadata` | ([docs.contextual.ai][13]) |

#### **/agents/{id}/tune**

| Verb       | Path                                                    |                            |
| ---------- | ------------------------------------------------------- | -------------------------- |
| **POST**   | `/agents/{agent_id}/tune`  *(Submit Training Job)*      |                            |
| **GET**    | `/agents/{agent_id}/tune`  *(List Tune Jobs)*           |                            |
| **GET**    | `/agents/{agent_id}/tune/{job_id}`  *(Get Tune Job)*    |                            |
| **DELETE** | `/agents/{agent_id}/tune/{job_id}`  *(Cancel Tune Job)* |                            |
| **GET**    | `/agents/{agent_id}/tune/models`  *(List Tuned Models)* | ([docs.contextual.ai][13]) |

---

### **/lmunit**

| Verb     | Path      |                            |
| -------- | --------- | -------------------------- |
| **POST** | `/lmunit` | ([docs.contextual.ai][14]) |

---

### **/users**

| Verb       | Path                      |                                                                                                          |
| ---------- | ------------------------- | -------------------------------------------------------------------------------------------------------- |
| **GET**    | `/users`                  |                                                                                                          |
| **PUT**    | `/users`                  |                                                                                                          |
| **POST**   | `/users` *(Invite Users)* |                                                                                                          |
| **DELETE** | `/users`                  | ([docs.contextual.ai][15], [docs.contextual.ai][13], [docs.contextual.ai][16], [docs.contextual.ai][17]) |

---

### **/generate**

| Verb     | Path        |                            |
| -------- | ----------- | -------------------------- |
| **POST** | `/generate` | ([docs.contextual.ai][18]) |

---

### **/rerank**

| Verb     | Path      |                            |
| -------- | --------- | -------------------------- |
| **POST** | `/rerank` | ([docs.contextual.ai][19]) |

---

### **/parse**

| Verb     | Path                           |                                                                                                          |
| -------- | ------------------------------ | -------------------------------------------------------------------------------------------------------- |
| **POST** | `/parse`                       |                                                                                                          |
| **GET**  | `/parse/jobs/{job_id}/status`  |                                                                                                          |
| **GET**  | `/parse/jobs/{job_id}/results` |                                                                                                          |
| **GET**  | `/parse/jobs`                  | ([docs.contextual.ai][20], [docs.contextual.ai][21], [docs.contextual.ai][22], [docs.contextual.ai][23]) |

---

*(All endpoints are listed verbatim; no sections have been condensed or removed.)*

[1]: https://docs.contextual.ai/api-reference "List Datastores - Contextual AI Documentation"
[2]: https://docs.contextual.ai/api-reference/datastores-documents/list-documents "List Documents - Contextual AI Documentation"
[3]: https://docs.contextual.ai/api-reference/datastores-documents/ingest-document "Ingest Document - Contextual AI Documentation"
[4]: https://docs.contextual.ai/api-reference/datastores-documents/get-document-metadata "Get Document Metadata - Contextual AI Documentation"
[5]: https://docs.contextual.ai/api-reference/datastores-documents/update-document-metadata "Update Document Metadata - Contextual AI Documentation"
[6]: https://docs.contextual.ai/api-reference/datastores-documents/delete-document "Delete Document - Contextual AI Documentation"
[7]: https://docs.contextual.ai/api-reference/agents/list-agents "List Agents - Contextual AI Documentation"
[8]: https://docs.contextual.ai/api-reference/agents/reset-agent "Reset Agent - Contextual AI Documentation"
[9]: https://docs.contextual.ai/api-reference/agents-query/query "Query - Contextual AI Documentation"
[10]: https://docs.contextual.ai/api-reference/agents-query/get-retrieval-info "Get Retrieval Info - Contextual AI Documentation"
[11]: https://docs.contextual.ai/api-reference/agents-query/provide-feedback "Provide Feedback - Contextual AI Documentation"
[12]: https://docs.contextual.ai/api-reference/agents-query/get-metrics "Get Metrics - Contextual AI Documentation"
[13]: https://docs.contextual.ai/api-reference/users/update-user "Update User - Contextual AI Documentation"
[14]: https://docs.contextual.ai/api-reference/lmunit/lmunit "LMUnit - Contextual AI Documentation"
[15]: https://docs.contextual.ai/api-reference/users/get-users "Get Users - Contextual AI Documentation"
[16]: https://docs.contextual.ai/api-reference/users/invite-users "Invite Users - Contextual AI Documentation"
[17]: https://docs.contextual.ai/api-reference/users/remove-user "Remove User - Contextual AI Documentation"
[18]: https://docs.contextual.ai/api-reference/generate/generate "Generate - Contextual AI Documentation"
[19]: https://docs.contextual.ai/api-reference/rerank/rerank "Rerank - Contextual AI Documentation"
[20]: https://docs.contextual.ai/api-reference/parse/parse-file "Parse File - Contextual AI Documentation"
[21]: https://docs.contextual.ai/api-reference/parse/parse-status "Parse Status - Contextual AI Documentation"
[22]: https://docs.contextual.ai/api-reference/parse/parse-result "Parse Result - Contextual AI Documentation"
[23]: https://docs.contextual.ai/api-reference/parse/parse-list-jobs "Parse List Jobs - Contextual AI Documentation"
