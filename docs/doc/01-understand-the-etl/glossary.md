---
layout: base
title: PHP-ETL - Understand the ETL
subTitle: Glossary
width: large
---

## Glossary

This glossary defines common terms used throughout the PHP-ETL documentation.

### A

**Asynchronous Item**
: An item that represents a task that is being processed in the background, allowing the main ETL chain to continue without waiting for the task to complete. This is useful for long-running operations like API calls.

### B

**Branch**
: An independent chain of operations that is created by a `split` or `merge` operation. Branches allow for parallel processing of data.

### C

**Chain**
: A sequence of operations that are executed in a specific order to process data.

**Chain Processor**
: The component that is responsible for executing an ETL chain.

**ChainBreak**
: An item that, when returned by an operation, stops the processing of the current data item in the chain. It effectively filters the item from subsequent steps.

### D

**DataItem**
: The most common type of item, representing a single unit of data (e.g., a row from a CSV file or a record from a database).

### E

**ETL**
: An acronym for Extract, Transform, and Load, which is a process for moving data from one system to another.

**Execution Context**
: An object that holds information and resources shared across all operations in a single ETL chain execution. This can include things like logging, file system access, and configuration parameters.

### F

**FileExtractedItem**
: An item that is generated after an operation has finished reading a file. It signals that the file has been processed and can be archived or deleted.

**FileLoadedItem**
: An item that is generated after data has been written to a file. It signals that the file is ready to be moved or used by other processes.

**Flysystem**
: A file storage library for PHP that provides a unified interface for working with different file systems, such as local storage, FTP, and cloud storage.

### G

**GroupedItem**
: An item that contains an iterator of other items. This is useful for processing large datasets in chunks without loading the entire dataset into memory.

### I

**Item**
: A unit of data that is passed between operations in a chain. There are different types of items, each with a specific purpose.

### O

**Operation**
: A single step in an ETL chain that performs a specific task, such as reading a file, transforming data, or writing to a database.

### R

**Rule Engine**
: A component that allows for data transformation using a flexible set of rules. This is often used to modify, add, or remove columns from a dataset.

### S

**Standalone**
: Running the ETL library without a specific framework integration like Symfony or Sylius.

**StopItem**
: A special item that is sent through the chain when there is no more data to process. It signals to operations that they should perform any final cleanup tasks, such as closing file handles.

**Subchain**
: A reusable chain of operations that can be called from within another chain. This is useful for encapsulating common transformation logic.
