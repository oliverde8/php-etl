---
layout: base
title: PHP-ETL - Understand the ETL 
subTitle: Item types
---


## ETL Item Types

{% capture description %}
This ETL framework processes data through various "operations," each receiving and returning "items" in a chain. Each item type serves a specific function in the ETL process, allowing for control over data extraction, transformation, and loading, as well as file handling and flow control.
{% endcapture %}
{% capture code %}
**Legend** 
- 📥 - Can be received as input
- 📤 - Can be returned as output
{% endcapture %}
{% include block/etl-step.html code=code description=description %}

### 1. DataItem 📥|📤
Encapsulates the data passed between operations. A `DataItem` contains data to be transformed or processed within each step in the chain.

**Usage:** Allows data to flow from one operation to the next.

### 2. FileExtractedItem 📥|📤
Generated after an operation has finished reading a file. This item is essential for post-read operations like file archival or deletion. Each line or data entry in the file generates a `DataItem` before the final `FileExtractedItem` is returned.

**Usage:** Signals the completion of file reading, enabling downstream operations to handle the file.

### 3. FileLoadedItem 📥|📤
Returned once all `DataItems` have been written to a file and the file handler is closed. This allows post-write operations like moving the file to external storage (e.g., SFTP, FTP, cloud storage) or archiving it.

**Usage:** Marks file load completion, enabling file transfers or other final transformations.

---

## Complex Items
These items are either generated or consumed by the ETL's internal `ChainProcessor`, adding further control to data flow and item management within the chain.

### 4. GroupedItem ❌|📤
Contains an iterator, allowing data to be processed incrementally without loading the entire dataset into memory. Although an operation can return a `GroupedItem`, it cannot receive one as input.

**Usage:** Allows iterative data extraction, keeping memory usage low by handling data as individual items downstream.

### 5. ChainBreak ❌|📤
Used to stop the chain for a specific item. When an operation returns a `ChainBreak`, the `ChainProcessor` halts further processing for the associated `DataItem`.

**Usage:** Prevents specific `DataItems` from proceeding further in the ETL chain.

### 6. MixItem ❌|📤
Enables returning multiple item types simultaneously. For instance, if an operation needs to return a `GroupedItem` along with a `FileExtractedItem`, it can use a `MixItem`. Although it can be returned, it is not a valid input type for operations.

**Usage:** Supports complex outputs by encapsulating various item types together.

### 7. StopItem 📥|📤
Signifies the end of data in the ETL chain and cannot be newly instantiated. A `StopItem` is sent through the chain when the ETL input is exhausted. Operations like file loading handle the `StopItem` by performing cleanup tasks (e.g., closing file handles). Once processed, a `StopItem` is returned either directly or within a `MixItem` to signal the ETL chain’s end.

**Usage:** Marks the end of data processing, triggering cleanup or finalization steps within operations.

### 8. AsynchronousItem ❌|📤
The `AsynchronousItem` is returned by an operation when processing occurs asynchronously, enabling non-blocking, background tasks within the ETL chain. This item type allows an operation to initiate a process that completes outside the main processing thread, letting the chain continue to execute other operations in parallel.

The `ChainProcessor` monitors each `AsynchronousItem` periodically and, once an asynchronous task is completed, the chain resumes with the item encapsulated within the `AsynchronousItem`. By default, the `ChainProcessor` can handle up to 10 asynchronous jobs concurrently; once this limit is reached, further processing pauses until at least one asynchronous task completes.

**Usage:** Facilitates background processing, enabling parallel execution of time-intensive operations without holding up the ETL chain.


#### Example: HttpClient Operation
The native `HttpClient` operation is a prime example of an asynchronous process that returns an `AsynchronousItem`. When `HttpClient` is used to make external API requests or perform network-related tasks, it returns an `AsynchronousItem` so the ETL process can proceed without waiting for the network response to complete. The `ChainProcessor` will monitor the `HttpClient` task and continue processing other items. Once the network response is received, the chain resumes processing with the data received from the `HttpClient` operation.


---